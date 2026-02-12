jQuery(document).ready(function ($) {
    $(document).on('click', '.toggle-featured-product', function (e) {
        e.preventDefault();
        var $button = $(this);
        var post_id = $button.data('id');
        var nonce = $button.data('nonce');
        var title = $button.data('title');
        var status = $button.data('status');
        var $icon = $button.find('.dashicons');

        $button.css('opacity', '0.5');

        $.ajax(
            {
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'toggle_featured_product',
                    post_id: post_id,
                    nonce: nonce
                },
                success: function (res) {
                    $button.css('opacity', '1');
                    if (res.success) {
                        $icon.removeClass('dashicons-star-filled dashicons-star-empty').addClass(res.data.icon_class).css('color', res.data.color);

                    } else {
                        alert(res.data || 'erroor updating statis');
                    }
                },
                error: function (err) {
                    $button.css('opacity', '1');
                    alert('Request failed');
                }
            }

        )


    })
})