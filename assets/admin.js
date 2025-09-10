/* global DokaBunnyAdmin, jQuery */
(function ($) {
    $(function () {
        const $btn = $('#doka-bunny-test');
        const $out = $('#doka-bunny-test-result');
        if (!$btn.length) return;

        $btn.on('click', function () {
            $out.text('…');
            $.ajax({
                method: 'GET',
                url: DokaBunnyAdmin.testRoute,
                headers: { 'X-WP-Nonce': DokaBunnyAdmin.nonce }
            }).done(function (res) {
                if (res && res.ok) {
                    $out.text('✔ Connection OK');
                    $out.css('color', '#22863a');
                } else {
                    $out.text((res && res.error) ? res.error : 'Failed to connect');
                    $out.css('color', '#d63638');
                }
            }).fail(function () {
                $out.text('Request failed');
                $out.css('color', '#d63638');
            });
        });
    });
})(jQuery);
