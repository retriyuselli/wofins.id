<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\DataPembayaran;
use App\Models\DocumentAttachment;
use App\Models\Order;
use App\Models\SubscriptionOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SecureFileController extends Controller
{
    public function order(Request $request, Order $order, string $field): Response
    {
        abort_unless(in_array($field, ['doc_kontrak', 'agreement_product'], true), 404);
        Gate::authorize('view', $order);

        return $this->stream($order->{$field}, download: $request->boolean('download'));
    }

    public function payment(DataPembayaran $payment): Response
    {
        Gate::authorize('view', $payment);

        return $this->stream($payment->image);
    }

    public function attachment(DocumentAttachment $attachment): Response
    {
        Gate::authorize('view', $attachment->document);

        return $this->stream($attachment->file_path, $attachment->file_name);
    }

    public function userDocument(User $user, string $field, ?int $index = null): Response
    {
        abort_unless(in_array($field, ['contract_document', 'identity_document', 'additional_documents'], true), 404);
        Gate::authorize('view', $user);

        $value = $user->{$field};
        if ($field === 'additional_documents') {
            $value = is_array($value) ? ($value[$index ?? 0] ?? null) : null;
        }

        return $this->stream($value);
    }

    public function companyLegal(Company $company, int $index): Response
    {
        Gate::authorize('view', $company);
        $documents = is_array($company->legal_documents) ? $company->legal_documents : [];

        return $this->stream($documents[$index] ?? null);
    }

    public function subscriptionProof(SubscriptionOrder $subscriptionOrder): Response
    {
        Gate::authorize('view', $subscriptionOrder);

        return $this->stream($subscriptionOrder->payment_proof_path);
    }

    private function stream(mixed $value, ?string $downloadName = null, bool $download = false): Response
    {
        $path = $this->path($value);
        abort_if($path === null, 404);

        $headers = [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ];
        $filename = $downloadName ?: basename($path);

        foreach (['private', 'public'] as $disk) {
            if (! Storage::disk($disk)->exists($path)) {
                continue;
            }

            if ($disk === 'public' && ! Storage::disk('private')->exists($path)) {
                $stream = Storage::disk('public')->readStream($path);
                if (is_resource($stream)) {
                    Storage::disk('private')->writeStream($path, $stream);
                    fclose($stream);
                }
            }

            return $download
                ? Storage::disk($disk)->download($path, $filename, $headers)
                : Storage::disk($disk)->response($path, $filename, $headers);
        }

        abort(404);
    }

    private function path(mixed $value): ?string
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $decoded;
                }
            }
        }

        if (is_array($value)) {
            $value = collect($value)->filter(fn ($item) => is_string($item) && $item !== '')->last();
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', trim($value)), '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return ($path === '' || str_contains($path, '..')) ? null : $path;
    }
}
