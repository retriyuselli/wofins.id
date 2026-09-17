<?php

namespace Tests\Feature;

use App\Http\Controllers\ReconciliationController;
use App\Http\Middleware\EnsureCompanySubscriptionActive;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Services\MobileModuleService;
use App\Support\PricingPlans;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantIsolationHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name');
            $table->string('subscription_plan')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(false);
            $table->timestamps();
        });
        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('category_id');
            $table->string('status');
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('stock')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('expense_ops', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('amount');
            $table->foreignId('payment_method_id')->nullable();
            $table->date('date_expense');
            $table->string('kategori_transaksi')->default('uang_keluar');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('expense_ops');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('users');
        Schema::dropIfExists('companies');

        parent::tearDown();
    }

    public function test_tenant_scope_hides_other_company_and_overwrites_forged_company_on_create(): void
    {
        [$companyA, $companyB] = $this->companies();
        $this->actingAsCompany($companyA);

        $category = new Category([
            'company_id' => $companyB->id,
            'name' => 'Kategori A',
            'slug' => 'kategori-a',
            'is_active' => true,
        ]);
        $category->disableLogging();
        $category->save();

        DB::table('categories')->insert([
            'company_id' => $companyB->id,
            'name' => 'Kategori B',
            'slug' => 'kategori-b',
            'is_active' => true,
            'is_visible' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame($companyA->id, $category->company_id);
        $this->assertSame([$category->id], Category::query()->pluck('id')->all());
    }

    public function test_expired_company_is_rejected_for_json_web_endpoint(): void
    {
        [$company] = $this->companies();
        $company->disableLogging();
        $company->forceFill(['subscription_expires_at' => now()->subDay()])->save();
        $user = $this->actingAsCompany($company);

        $request = Request::create('/admin/reconciliation/unmark', 'POST');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $user);

        $response = (new EnsureCompanySubscriptionActive)->handle(
            $request,
            fn () => response()->json(['ok' => true]),
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_mobile_product_rejects_vendor_from_another_company(): void
    {
        [$companyA, $companyB] = $this->companies();
        $this->actingAsCompany($companyA);

        $categoryId = DB::table('categories')->insertGetId([
            'company_id' => $companyB->id,
            'name' => 'Vendor B',
            'slug' => 'vendor-b',
            'is_active' => true,
            'is_visible' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vendorId = DB::table('vendors')->insertGetId([
            'company_id' => $companyB->id,
            'category_id' => $categoryId,
            'status' => 'product',
            'name' => 'Vendor Company B',
            'slug' => 'vendor-company-b',
            'stock' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = (new Product)->forceFill(['company_id' => $companyA->id]);
        $method = new \ReflectionMethod(MobileModuleService::class, 'assertProductVendorsBelongToCompany');

        try {
            $method->invoke(new MobileModuleService, $product, [['vendor_id' => $vendorId]]);
            $this->fail('Vendor lintas company seharusnya ditolak.');
        } catch (HttpResponseException $exception) {
            $this->assertSame(422, $exception->getResponse()->getStatusCode());
        }
    }

    public function test_reconciliation_cannot_resolve_transaction_from_another_company(): void
    {
        [$companyA, $companyB] = $this->companies();
        $this->actingAsCompany($companyA);

        $sourceId = DB::table('expense_ops')->insertGetId([
            'company_id' => $companyB->id,
            'name' => 'Biaya Company B',
            'amount' => 100000,
            'date_expense' => now()->toDateString(),
            'kategori_transaksi' => 'uang_keluar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $method = new \ReflectionMethod(ReconciliationController::class, 'sourceRecord');

        $this->expectException(ModelNotFoundException::class);
        $method->invoke(new ReconciliationController, 'expense_ops', $sourceId, 1);
    }

    public function test_enterprise_plan_uses_its_defined_features_and_limits(): void
    {
        $this->assertTrue(PricingPlans::allows('enterprise', PricingPlans::FEATURE_ADVANCED_REPORTS));
        $this->assertNull(PricingPlans::limit('enterprise', 'orders'));
    }

    /**
     * @return array{Company, Company}
     */
    private function companies(): array
    {
        return [
            $this->company('A'),
            $this->company('B'),
        ];
    }

    private function company(string $suffix): Company
    {
        $id = DB::table('companies')->insertGetId([
            'company_name' => "Company {$suffix}",
            'subscription_plan' => 'business',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Company::query()->findOrFail($id);
    }

    private function actingAsCompany(Company $company): User
    {
        $user = (new User)->forceFill([
            'id' => $company->id,
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $user->exists = true;
        $user->setRelation('roles', collect());
        $this->actingAs($user);

        return $user;
    }
}
