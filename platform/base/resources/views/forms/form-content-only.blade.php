@if ($showStart)
    {!! Form::open(Arr::except($formOptions, ['template'])) !!}
@endif

@if ($showFields)
    {{ $form->getOpenWrapperFormColumns() }}

    @foreach ($fields as $field)
        @continue(in_array($field->getName(), $exclude))

        {!! $field->render() !!}
    @endforeach

    {{ $form->getCloseWrapperFormColumns() }}

    @unless ($form->isWithoutActionButtons())
        <div class="mt-3 text-end">
            @include('core/base::forms.partials.form-buttons', ['onlySave' => true])
        </div>
    @endunless
@endif

@if ($showEnd)
    {!! Form::close() !!}
@endif

@if ($form->getValidatorClass())
    @push('footer')
        {!! $form->renderValidatorJs() !!}
    @endpush
@endif
