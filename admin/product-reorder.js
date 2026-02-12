jQuery(document).ready(function ($) {
    var $tbody = $('#the-list');

    $tbody.sortable({
        'items': 'tr',
        cursor: 'move',
        axis: 'y',
        handle: '.column-featured',
        placeholder: 'ui-sortable-placeholder',
        helper: function (e, ui) {
            ui.children().each(function () {
                $(this).width($(this).width());


            });
            return ui;
        },
        update: function (e, ui) {
            var order = [];
            $tbody.find('tr').each(function (index) {
                var id = $(this).attr('id').replace('post-', '');
                order.push(id);
            });

            $tbody.css('opacity', '0.5');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ruixing_save_product_order',
                    order: order,
                    nonce: ruixing_product_reorder.nonce
                },
                success: function (response) {
                    $tbody.css('opacity', '1');

                    if (!response.success) {
                        alert(response.data || 'Error saving order');

                    }

                },
                error: function (response) {
                    $tbody.css('opacity', '1');
                    alert('Request failed');
                }

            })


        }

    }).disableSelection();

    $('.column-featured').css('cursor', 'move');
})