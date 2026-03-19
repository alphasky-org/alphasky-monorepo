$(() => {
    if ($(document).find('.colorpicker-input').length > 0) {
        $(document).find('.colorpicker-input').colorpicker()
    }

    if ($(document).find('.iconpicker-input').length > 0) {
        $(document).find('.iconpicker-input').iconpicker({
            selected: true,
            hideOnSelect: true,
        })
    }

    $(document).on('submit', '.theme-option form', (event) => {
        event.preventDefault()

        const $form = $(event.currentTarget)
        const $button = $form.find('button[type="submit"]')

        if (typeof tinymce != 'undefined') {
            for (let instance in tinymce.editors) {
                if (tinymce.editors[instance].getContent) {
                    $('#' + instance).html(tinymce.editors[instance].getContent())
                }
            }
        }

        Alphasky.showButtonLoading($button)

        $httpClient
            .make()
            .post($form.prop('action'), new FormData($form[0]))
            .then(({ data }) => {
                Alphasky.showSuccess(data.message)
                $form.removeClass('dirty')
            })
            .finally(() => {
                Alphasky.hideButtonLoading($button)
            })
    })

    $('.theme-option button[data-bs-toggle="pill"]').on('shown.bs.tab', () => {
        Alphasky.initResources()

        if (typeof EditorManagement != 'undefined') {
            window.EDITOR = new EditorManagement().init()
            window.EditorManagement = window.EditorManagement || EditorManagement
        }
    })
})
