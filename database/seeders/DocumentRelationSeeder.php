<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\DocumentAttachment;
use App\Models\DocumentRecipient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DocumentRelationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding DocumentApproval, DocumentAttachment & DocumentRecipient...');

        $documents = Document::all();
        $users = User::query()->orderBy('id')->get();

        if ($documents->isEmpty()) {
            $this->command->error('No documents found. Run DocumentSeeder first.');

            return;
        }

        if ($users->count() < 2) {
            $this->command->error('Need at least 2 users for document relations.');

            return;
        }

        $approvalCount = 0;
        $attachmentCount = 0;
        $recipientCount = 0;

        foreach ($documents as $docIndex => $document) {
            $creator = User::find($document->created_by) ?? $users->first();
            $approvers = $users->where('id', '!=', $creator->id)->values();

            // 1–2 approval steps per document
            $steps = min(2, $approvers->count());
            for ($step = 1; $step <= $steps; $step++) {
                $approver = $approvers[($docIndex + $step) % $approvers->count()];
                $status = match (true) {
                    in_array($document->status, ['approved', 'published'], true) => 'approved',
                    $document->status === 'rejected' => 'rejected',
                    $document->status === 'draft' => 'pending',
                    default => collect(['pending', 'approved', 'revised'])->random(),
                };

                DocumentApproval::query()->updateOrCreate(
                    [
                        'document_id' => $document->id,
                        'user_id' => $approver->id,
                        'step_order' => $step,
                    ],
                    [
                        'status' => $status,
                        'note' => 'Approval step '.$step.' oleh '.$approver->name,
                        'signed_at' => in_array($status, ['approved', 'rejected'], true)
                            ? Carbon::now()->subDays(rand(1, 20))
                            : null,
                        'signature_path' => $status === 'approved'
                            ? 'signatures/seed-'.$approver->id.'.png'
                            : null,
                    ]
                );
                $approvalCount++;
            }

            // Attachment
            DocumentAttachment::query()->updateOrCreate(
                [
                    'document_id' => $document->id,
                    'file_name' => 'lampiran-'.$document->id.'.pdf',
                ],
                [
                    'file_path' => 'documents/attachments/seed-'.$document->id.'.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => rand(50_000, 2_000_000),
                ]
            );
            $attachmentCount++;

            // Recipients: creator + 2 other users
            $recipientUsers = $users->shuffle()->take(3);
            foreach ($recipientUsers as $rIndex => $recipient) {
                DocumentRecipient::query()->updateOrCreate(
                    [
                        'document_id' => $document->id,
                        'user_id' => $recipient->id,
                    ],
                    [
                        'read_at' => $rIndex === 0 ? Carbon::now()->subDays(rand(0, 10)) : null,
                        'is_cc' => $rIndex === 2,
                    ]
                );
                $recipientCount++;
            }
        }

        $this->command->info("✅ Approvals: {$approvalCount}, Attachments: {$attachmentCount}, Recipients: {$recipientCount}");
    }
}
