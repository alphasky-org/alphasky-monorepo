@php
    $currentValue = old($name, $options['value'] ?? $options['default_value'] ?? '');
    $initialSchema = $currentValue ?: json_encode([
        'meta' => ['source' => 'profession-form-builder'],
        'pages' => [
            ['id' => 'page_1', 'title' => trans('core/base::base.core_information')],
            ['id' => 'page_2', 'title' => trans('core/base::base.additional_details')],
        ],
        'fields' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $translations = [
        'create_form' => trans('core/base::base.create_form'),
        'form_builder_hint' => trans('core/base::base.form_builder_hint'),
        'form_builder_summary' => trans('core/base::base.form_builder_summary'),
        'form_builder_title' => trans('core/base::base.form_builder_title'),
        'form_builder_description' => trans('core/base::base.form_builder_description'),
        'close' => trans('core/base::base.close'),
        'builder_components' => trans('core/base::base.builder_components'),
        'form_pages' => trans('core/base::base.form_pages'),
        'form_pages_hint' => trans('core/base::base.form_pages_hint'),
        'add_page' => trans('core/base::base.add_page'),
        'field_settings_title' => trans('core/base::base.field_settings_title'),
        'select_field_hint' => trans('core/base::base.select_field_hint'),
        'field_page' => trans('core/base::base.field_page'),
        'field_label' => trans('core/base::base.field_label'),
        'field_condition' => trans('core/base::base.field_condition'),
        'field_helper' => trans('core/base::base.field_helper'),
        'field_required' => trans('core/base::base.field_required'),
        'field_options' => trans('core/base::base.field_options'),
        'add_option' => trans('core/base::base.add_option'),
        'field_number_min' => trans('core/base::base.field_number_min'),
        'field_number_max' => trans('core/base::base.field_number_max'),
        'field_default_color' => trans('core/base::base.field_default_color'),
        'form_empty_page' => trans('core/base::base.form_empty_page'),
        'form_json_hint' => trans('core/base::base.form_json_hint'),
        'semantic_text_hint' => trans('core/base::base.semantic_text_hint'),
    ];

    $builderId = 'form-builder-' . uniqid();
@endphp
 <script src="/vendor/core/core/base/js/tailwindcss.js"></script>
    <script src="/vendor/core/core/base/js/alpinejs.min.js" defer></script>
    <script src="/vendor/core/core/base/js/form-builder.js" defer></script>
 <script>
  tailwind.config = {
    prefix: 'tw-', // جميع كلاسات Tailwind ستبدأ بـ tw- مثل: tw-flex, tw-hidden
    corePlugins: {
      preflight: false, // تعطيل Tailwind Reset لمنع تداخل التنسيقات الأساسية
    }
  }
</script>
<x-core::form.field
    :showLabel="$showLabel"
    :showField="$showField"
    :options="$options"
    :name="$name"
    :showError="$showError"
    :nameKey="$nameKey"
>
    <x-slot:label>
        @if ($showLabel && $options['label'] !== false && $options['label_show'])
            {!! Form::customLabel($name, $options['label'], $options['label_attr']) !!}
        @endif
    </x-slot:label>

    <style>
        .glass-panel {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .semantic-text {
            font-family: serif;
            letter-spacing: 0.05em;
        }
    </style>

    <div
        id="{{ $builderId }}"
        class="tw-relative"
        data-form-builder-root
        data-initial-schema='@json($initialSchema)'
        data-translations='@json($translations)'
    >
        {!! Form::hidden($name, $currentValue, array_merge($options['attr'] ?? [], ['data-form-builder-input' => '1'])) !!}

        <!-- Base UI Card (Always Visible) -->
        <div class="glass-card tw-flex tw-items-center tw-justify-between tw-gap-4 tw-rounded-3xl tw-p-5 tw-shadow-2xl tw-shadow-black/40">
            <div class="tw-flex tw-items-center tw-gap-4">
                <div class="tw-flex tw-h-14 tw-w-14 tw-items-center tw-justify-center tw-rounded-2xl tw-bg-gradient-to-br tw-from-emerald-400/20 tw-to-teal-600/20 tw-p-px tw-shadow-inner">
                    <div class="tw-flex tw-h-full tw-w-full tw-items-center tw-justify-center tw-rounded-2xl tw-bg-slate-950/40 tw-text-emerald-400">
                        <i class="fa-solid fa-wand-magic-sparkles tw-text-xl"></i>
                    </div>
                </div>
                <div>
                    <h4 class="tw-text-sm tw-font-black tw-tracking-tight tw-text-white">{{ trans('core/base::base.form_builder_title') }}</h4>
                    <p class="tw-mt-0.5 tw-text-[11px] tw-leading-relaxed tw-text-slate-400 tw-max-w-xs">{{ trans('core/base::base.form_builder_summary') }}</p>
                </div>
            </div>
            
            <button type="button" 
                class="tw-group tw-relative tw-inline-flex tw-items-center tw-gap-2.5 tw-overflow-hidden tw-rounded-2xl tw-bg-emerald-500 tw-px-2 tw-py-2 tw-text-xs tw-font-black tw-uppercase tw-tracking-widest tw-text-white tw-shadow-lg tw-shadow-emerald-500/20 tw-transition-all hover:tw-scale-[1.02] hover:tw-bg-emerald-400 hover:tw-shadow-emerald-500/40 active:tw-scale-95" 
                data-form-builder-open>
                <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-r tw-from-transparent tw-via-white/20 tw-to-transparent tw-transition-transform tw-duration-500 -tw-translate-x-full group-hover:tw-translate-x-full"></div>
                <i class="fa-solid fa-plus-circle tw-text-sm"></i>
                <span>{{ trans('core/base::base.create_form') }}</span>
            </button>
        </div>

        <!-- Fullscreen Glass Modal -->
        <div class="tw-fixed tw-inset-0 tw-z-[9999] tw-hidden tw-items-center tw-justify-center tw-bg-slate-950/60 tw-p-1 tw-backdrop-blur-md tw-transition-all tw-duration-300 sm:tw-p-8" data-form-builder-modal>
            <div class="glass-panel tw-flex tw-h-full tw-max-h-[900px] tw-w-full tw-max-w-[1400px] tw-flex-col tw-overflow-hidden tw-rounded-[2.5rem] tw-shadow-[0_0_100px_rgba(0,0,0,0.5)]">
                
                <!-- Modal Header -->
                <header class="tw-flex tw-items-center tw-justify-between tw-border-b tw-border-white/5 tw-bg-white/[0.02] tw-px-2 tw-py-2">
                    <div class="tw-flex tw-items-center tw-gap-5">
                        <div class="tw-flex tw-h-12 tw-w-12 tw-items-center tw-justify-center tw-rounded-2xl tw-bg-emerald-500/10 tw-text-emerald-400 tw-shadow-inner">
                            <i class="fa-solid fa-boxes-stacked tw-text-xl"></i>
                        </div>
                        <div>
                            <h2 class="tw-text-lg tw-font-black tw-tracking-tight tw-text-white">{{ trans('core/base::base.form_builder_title') }}</h2>
                            <p class="tw-text-xs tw-font-medium tw-text-slate-400">{{ trans('core/base::base.form_builder_description') }}</p>
                        </div>
                    </div>

                    <button type="button" 
                        class="tw-flex tw-h-12 tw-w-12 tw-items-center tw-justify-center tw-rounded-2xl tw-border tw-border-white/10 tw-bg-white/5 tw-text-slate-400 tw-transition hover:tw-bg-rose-500/10 hover:tw-text-rose-400 hover:tw-border-rose-500/20" 
                        data-form-builder-close>
                        <i class="fa-solid fa-xmark tw-text-lg"></i>
                    </button>
                </header>

                <!-- Modal Body -->
                <div class="tw-grid tw-flex-1 tw-grid-cols-1 tw-overflow-hidden lg:tw-grid-cols-12">
                    
                    <!-- Left Palette (Components) -->
                    <aside class="custom-scrollbar tw-space-y-1 tw-overflow-y-auto tw-border-r tw-border-white/5 tw-bg-black/10 tw-p-1 lg:tw-col-span-3" data-form-builder-components></aside>

                    <!-- Center Canvas (Fields) -->
                    <main class="tw-flex tw-flex-col tw-overflow-hidden tw-bg-black/5 lg:tw-col-span-6">
                        <!-- Pages Bar -->
                        <div class="tw-border-b tw-border-white/5 tw-bg-white/[0.02] tw-px-2 tw-py-2">
                            <div class="tw-flex tw-items-center tw-justify-between tw-mb-5">
                                <div class="tw-flex tw-items-baseline tw-gap-2">
                                    <h4 class="tw-text-xs tw-font-black tw-uppercase tw-tracking-[0.25em] tw-text-slate-200">{{ trans('core/base::base.form_pages') }}</h4>
                                    <span class="tw-text-[10px] tw-font-bold tw-text-emerald-500 tw-opacity-60">Step-by-step UI</span>
                                </div>
                                <button type="button" 
                                    class="tw-flex tw-items-center tw-gap-2 tw-rounded-xl tw-border tw-border-sky-400/20 tw-bg-sky-400/10 tw-px-2 tw-py-2 tw-text-[11px] tw-font-black tw-text-sky-300 tw-transition hover:tw-bg-sky-400/20" 
                                    data-form-builder-add-page>
                                    <i class="fa-solid fa-plus"></i>
                                    {{ trans('core/base::base.add_page') }}
                                </button>
                            </div>
                            <div class="tw-flex tw-flex-wrap tw-gap-2.5" data-form-builder-pages></div>
                        </div>

                        <!-- Fields Canvas -->
                        <div class="custom-scrollbar tw-flex-1 tw-space-y-1 tw-overflow-y-auto tw-p-2" data-form-builder-fields></div>
                    </main>

                    <!-- Right Inspector (Settings) -->
                    <aside class="custom-scrollbar tw-border-l tw-border-white/5 tw-bg-black/10 tw-p-2 tw-overflow-y-auto lg:tw-col-span-3">
                        <div class="tw-mb-6 tw-flex tw-items-center tw-gap-3">
                            <div class="tw-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-lg tw-bg-white/5 tw-text-slate-300">
                                <i class="fa-solid fa-sliders tw-text-xs"></i>
                            </div>
                            <h2 class="tw-text-xs tw-font-black tw-uppercase tw-tracking-[0.25em] tw-text-white">{{ trans('core/base::base.field_settings_title') }}</h2>
                        </div>
                        <div data-form-builder-settings></div>
                    </aside>
                </div>

                <!-- Modal Footer -->
                <footer class="tw-hidden tw-border-t tw-border-white/5 tw-bg-white/[0.02] tw-px-2 tw-py-2">
                    <div class="tw-flex tw-flex-col tw-gap-6 lg:tw-flex-row lg:tw-items-center">
                        <div class="tw-flex-1">
                            <div class="tw-flex tw-items-center tw-gap-2 tw-text-[11px] tw-font-bold tw-text-slate-400">
                                <i class="fa-solid fa-code tw-text-emerald-500/60"></i>
                                <span>{{ trans('core/base::base.form_json_hint') }}</span>
                            </div>
                            <textarea readonly 
                                class="custom-scrollbar tw-mt-3 tw-h-24 tw-w-full tw-rounded-2xl tw-border tw-border-white/5 tw-bg-slate-950/60 tw-p-4 tw-font-mono tw-text-[11px] tw-leading-relaxed tw-text-emerald-300/80 tw-shadow-inner tw-outline-none placeholder:tw-text-slate-700 focus:tw-border-emerald-500/30" 
                                data-form-builder-preview
                                placeholder="Schema JSON will appear here..."></textarea>
                        </div>
                        <div class="tw-flex tw-items-center tw-gap-3">
                            <button type="button" 
                                class="tw-rounded-2xl tw-border tw-border-white/10 tw-bg-white/5 tw-px-2 tw-py-2 tw-text-xs tw-font-black tw-uppercase tw-tracking-widest tw-text-slate-100 tw-transition hover:tw-bg-white/10" 
                                data-form-builder-close>
                                {{ trans('core/base::base.close') }}
                            </button>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</x-core::form.field>
