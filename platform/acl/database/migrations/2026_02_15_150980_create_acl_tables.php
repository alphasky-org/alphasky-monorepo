<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('code', 120);
            $table->boolean('completed')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title', 191);
            $table->string('action_label', 191)->nullable();
            $table->string('action_url', 191)->nullable();
            $table->string('description', 400)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->nullable();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
            $table->string('permission', 191)->nullable();
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->timestamps();
        });

        Schema::create('dashboard_widget_settings', function (Blueprint $table) {
            $table->id();
            $table->text('settings')->nullable();
            $table->foreignId('user_id')->index();
            $table->foreignId('widget_id')->index();
            $table->unsignedTinyInteger('order')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 191)->unique();
            $table->string('platform', 191)->nullable();
            $table->string('app_version', 191)->nullable();
            $table->string('device_id', 191)->nullable();
            $table->string('user_type', 191)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_type', 'user_id']);
            $table->index(['platform', 'is_active']);
            $table->index('is_active');
        });

        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('name', 191);
            $table->string('alt', 191)->nullable();
            $table->unsignedBigInteger('folder_id')->default(0);
            $table->string('mime_type', 120);
            $table->integer('size');
            $table->string('url', 191);
            $table->text('options')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('visibility', 191)->default('public');

            $table->index(['folder_id', 'user_id', 'created_at']);
        });

        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('name', 191)->nullable();
            $table->string('color', 191)->nullable();
            $table->string('slug', 191)->nullable();
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'user_id', 'created_at']);
        });

        Schema::create('media_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120);
            $table->text('value')->nullable();
            $table->unsignedBigInteger('media_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique()->nullable();
            $table->string('status', 60)->default('published');
            $table->timestamps();
        });

        Schema::create('menu_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id');
            $table->string('location', 191);
            $table->timestamps();

            $table->index(['menu_id', 'created_at']);
        });

        Schema::create('menu_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->index();
            $table->unsignedBigInteger('parent_id')->default(0)->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->string('reference_type', 191)->nullable()->index();
            $table->string('url', 191)->nullable();
            $table->string('icon_font', 191)->nullable();
            $table->unsignedTinyInteger('position')->default(0);
            $table->string('title', 191)->nullable();
            $table->string('css_class', 191)->nullable();
            $table->string('target', 20)->default('_self');
            $table->integer('show')->default(0);
            $table->unsignedTinyInteger('has_child')->default(0);
            $table->timestamps();
        });

        Schema::create('meta_boxes', function (Blueprint $table) {
            $table->id();
            $table->string('meta_key', 191);
            $table->text('meta_value')->nullable();
            $table->unsignedBigInteger('reference_id')->index();
            $table->string('reference_type', 120);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->longText('content')->nullable();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('image', 191)->nullable();
            $table->string('template', 60)->nullable();
            $table->string('description', 400)->nullable();
            $table->string('status', 60)->default('published');
            $table->timestamps();
        });

        Schema::create('pages_translations', function (Blueprint $table) {
            $table->string('lang_code', 20);
            $table->unsignedBigInteger('pages_id');
            $table->string('name', 191)->nullable();
            $table->string('description', 400)->nullable();
            $table->longText('content')->nullable();

            $table->primary(['lang_code', 'pages_id']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 191)->primary();
            $table->string('token', 191);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title', 191);
            $table->text('message');
            $table->string('type', 191)->default('general');
            $table->string('target_type', 191)->nullable();
            $table->string('target_value', 191)->nullable();
            $table->string('action_url', 191)->nullable();
            $table->string('image_url', 191)->nullable();
            $table->json('data')->nullable();
            $table->string('status', 191)->default('sent');
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('read_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('push_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('push_notification_id');
            $table->string('user_type', 50);
            $table->unsignedBigInteger('user_id');
            $table->string('device_token', 191)->nullable();
            $table->string('platform', 20)->nullable();
            $table->string('status', 20)->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->json('fcm_response')->nullable();
            $table->string('error_message', 191)->nullable();
            $table->timestamps();

            $table->index(['push_notification_id', 'user_type', 'user_id'], 'pnr_notification_user_index');
            $table->index(['user_type', 'user_id', 'status'], 'pnr_user_status_index');
            $table->index(['user_type', 'user_id', 'read_at'], 'pnr_user_read_index');
            $table->index('status');
        });

        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->string('revisionable_type', 191);
            $table->unsignedBigInteger('revisionable_id');
            $table->foreignId('user_id')->nullable();
            $table->string('key', 120);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();

            $table->index(['revisionable_id', 'revisionable_type']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 120);
            $table->text('permissions')->nullable();
            $table->string('description', 400)->nullable();
            $table->unsignedTinyInteger('is_default')->default(0);
            $table->foreignId('created_by')->index();
            $table->foreignId('updated_by')->index();
            $table->timestamps();
        });

        Schema::create('role_users', function (Blueprint $table) {
            $table->foreignId('user_id')->index();
            $table->foreignId('role_id')->index();
            $table->timestamps();

            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 191)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 120)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->string('first_name', 120)->nullable();
            $table->string('last_name', 120)->nullable();
            $table->string('username', 60)->unique()->nullable();
            $table->unsignedBigInteger('avatar_id')->nullable();
            $table->boolean('super_user')->default(0);
            $table->boolean('manage_supers')->default(0);
            $table->text('permissions')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->integer('boss')->nullable();
            $table->string('phone', 50)->nullable();
        });

        Schema::create('user_groups', function (Blueprint $table) {
            $table->foreignId('group_id')->index();
            $table->foreignId('user_id')->index();
        });

        Schema::create('user_meta', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->nullable();
            $table->text('value')->nullable();
            $table->foreignId('user_id')->index();
            $table->timestamps();
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->string('user_type', 191);
            $table->unsignedBigInteger('user_id');
            $table->string('key', 191);
            $table->json('value');
            $table->timestamps();

            $table->unique(['user_type', 'user_id', 'key']);
            $table->index(['user_type', 'user_id']);
            $table->index('key');
        });

        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->string('widget_id', 120);
            $table->string('sidebar_id', 120);
            $table->string('theme', 120);
            $table->unsignedTinyInteger('position')->default(0);
            $table->text('data')->nullable();
            $table->timestamps();
        });

        Schema::create('slugs', function (Blueprint $table) {
            $table->id();
            $table->string('key', 191)->index();
            $table->unsignedBigInteger('reference_id')->index();
            $table->string('reference_type', 191);
            $table->string('prefix', 120)->default('')->index();
            $table->timestamps();
            $table->index(['reference_id', 'reference_type']);
        });

        Schema::create('slugs_translations', function (Blueprint $table) {
            $table->string('lang_code', 20);
            $table->unsignedBigInteger('slugs_id');
            $table->string('key', 191)->nullable();
            $table->string('prefix', 120)->default('');
            $table->primary(['lang_code', 'slugs_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activations');
        Schema::dropIfExists('admin_notifications');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboard_widget_settings');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('media_folders');
        Schema::dropIfExists('media_settings');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('menu_locations');
        Schema::dropIfExists('menu_nodes');
        Schema::dropIfExists('meta_boxes');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('pages_translations');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('push_notifications');
        Schema::dropIfExists('push_notification_recipients');
        Schema::dropIfExists('revisions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('role_users');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_groups');
        Schema::dropIfExists('user_meta');
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('widgets');
        Schema::dropIfExists('slugs');
        Schema::dropIfExists('slugs_translations');
    }
};
