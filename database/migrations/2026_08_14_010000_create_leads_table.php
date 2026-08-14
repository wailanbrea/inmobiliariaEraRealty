<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->enum('source', [
                'contact_page', 'property_detail', 'publish_property',
                'investment_page', 'whatsapp_click', 'news_contact',
            ]);
            $table->string('name', 150);
            $table->string('phone', 30);
            $table->string('email', 190)->nullable();
            $table->text('message')->nullable();
            $table->json('details')->nullable();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('interest_type', 50)->nullable();
            $table->string('budget_range', 50)->nullable();
            $table->enum('preferred_contact', ['phone', 'whatsapp', 'email'])->nullable();
            $table->enum('status', [
                'new', 'contacted', 'interested', 'visit_scheduled',
                'negotiating', 'closed', 'lost', 'spam',
            ])->default('new');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('referrer_url', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['source', 'created_at']);
            $table->index(['assigned_to_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
