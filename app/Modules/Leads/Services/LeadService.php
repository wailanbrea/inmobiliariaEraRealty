<?php

namespace App\Modules\Leads\Services;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Modules\Leads\Mail\LeadConfirmationMail;
use App\Modules\Leads\Mail\NewLeadNotificationMail;
use App\Modules\Leads\Models\Lead;
use App\Modules\Settings\Services\MailConfigService;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class LeadService
{
    public function __construct(
        private MailConfigService $mailConfig,
        private SettingsService $settings,
    ) {}

    public function formToken(): string
    {
        return Crypt::encryptString((string) now()->timestamp);
    }

    public function create(LeadSource $source, array $data, Request $request, array $details = []): Lead
    {
        $lead = Lead::create([
            'source' => $source,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'message' => $data['message'] ?? null,
            'details' => $details ?: null,
            'property_id' => $data['property_id'] ?? null,
            'interest_type' => $data['interest_type'] ?? null,
            'preferred_contact' => $data['preferred_contact'] ?? null,
            'budget_range' => $data['budget_range'] ?? null,
            'status' => $this->isSpam($data) ? LeadStatus::Spam : LeadStatus::New,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'referrer_url' => mb_substr((string) $request->headers->get('referer'), 0, 500),
        ]);

        if ($lead->status !== LeadStatus::Spam) {
            $this->notify($lead);
        }

        return $lead;
    }

    private function isSpam(array $data): bool
    {
        if (filled($data['website'] ?? null)) {
            return true;
        }

        try {
            $createdAt = (int) Crypt::decryptString($data['form_token']);

            return now()->timestamp - $createdAt < 3;
        } catch (Throwable) {
            return true;
        }
    }

    private function notify(Lead $lead): void
    {
        $recipient = $this->mailConfig->formRecipient();
        $confirmationEnabled = (bool) $this->settings->get('mail_send_client_confirmation', false);
        $this->mailConfig->apply();

        if ($recipient) {
            $this->queueSafely($lead, fn () => Mail::to($recipient)->queue(new NewLeadNotificationMail($lead)), 'internal');
        }

        if ($confirmationEnabled && $lead->email) {
            $this->queueSafely($lead, fn () => Mail::to($lead->email)->locale(current_locale())->queue(new LeadConfirmationMail($lead)), 'confirmation');
        }
    }

    private function queueSafely(Lead $lead, callable $queue, string $type): void
    {
        try {
            $queue();
        } catch (Throwable $exception) {
            Log::error('No se pudo encolar un correo del lead.', [
                'lead_id' => $lead->id, 'type' => $type, 'exception' => $exception->getMessage(),
            ]);
        }
    }
}
