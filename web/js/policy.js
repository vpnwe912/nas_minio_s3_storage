$(document).ready(function() {
    // Инициализация tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Подтверждение удаления
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Вы уверены, что хотите удалить этот элемент?')) {
            e.preventDefault();
        }
    });
    
    // Динамическое добавление/удаление statement-ов
    $(document).on('click', '.add-statement', function(e) {
        e.preventDefault();
        var container = $(this).closest('.statements-container');
        var newItem = container.find('.statement-item').first().clone();
        newItem.find('input, textarea').val('');
        newItem.find('select').val('');
        newItem.find('.has-error').removeClass('has-error');
        newItem.find('.help-block').text('');
        container.append(newItem);
        updateStatementIndexes(container);
    });
    
    $(document).on('click', '.remove-statement', function(e) {
        e.preventDefault();
        var container = $(this).closest('.statements-container');
        if (container.find('.statement-item').length > 1) {
            $(this).closest('.statement-item').remove();
            updateStatementIndexes(container);
        }
    });
    
    function updateStatementIndexes(container) {
        container.find('.statement-item').each(function(index) {
            $(this).find('input, select, textarea, label').each(function() {
                var $el = $(this);
                var name = $el.attr('name');
                var id = $el.attr('id');
                var forAttr = $el.attr('for');
                
                if (name) {
                    $el.attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                }
                if (id) {
                    $el.attr('id', id.replace(/\d+/, index));
                }
                if (forAttr) {
                    $el.attr('for', forAttr.replace(/\d+/, index));
                }
            });
        });
    }
    
    // Инициализация Select2 для выпадающих списков
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    }
});