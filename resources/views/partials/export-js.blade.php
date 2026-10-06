<script>
    /**
     * Helper export berbasis queue.
     * requestExport(url, data, $btn): POST -> polling status -> otomatis download saat selesai.
     */
    (function () {
        function toast(message, type) {
            var $c = $('#export-toast-container');
            if (!$c.length) {
                $c = $('<div id="export-toast-container" style="position:fixed;top:1rem;right:1rem;z-index:2000;width:340px;"></div>').appendTo('body');
            }
            var $t = $('<div class="alert alert-' + (type || 'info') + ' shadow-sm mb-2"></div>').text(message).appendTo($c);
            setTimeout(function () { $t.fadeOut(300, function () { $t.remove(); }); }, 6000);
            return $t;
        }

        function poll(statusUrl, $btn, label, tries) {
            if (tries > 300) { // ~10 menit
                toast('Proses terlalu lama. Silakan coba lagi nanti.', 'warning');
                return done($btn, label);
            }
            $.getJSON(statusUrl).done(function (res) {
                if (res.status === 'done') {
                    toast('File siap, mengunduh...', 'success');
                    window.location.href = res.download_url;
                    return done($btn, label);
                }
                if (res.status === 'failed') {
                    toast(res.error || 'Gagal membuat file.', 'danger');
                    return done($btn, label);
                }
                setTimeout(function () { poll(statusUrl, $btn, label, tries + 1); }, 2000);
            }).fail(function () {
                toast('Gagal memeriksa status export.', 'danger');
                done($btn, label);
            });
        }

        function done($btn, label) {
            if ($btn) { $btn.prop('disabled', false).text(label); }
        }

        window.requestExport = function (url, data, $btn) {
            var label = $btn ? $btn.text() : '';
            if ($btn) { $btn.prop('disabled', true).text('Memproses...'); }

            var csrf = $('meta[name="csrf-token"]').attr('content');

            $.ajax({
                url: url,
                method: 'POST',
                data: data || {},
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).done(function (res) {
                toast('Permintaan diterima. File sedang dibuat di background...', 'info');
                poll(res.status_url, $btn, label, 0);
            }).fail(function (xhr) {
                var msg = 'Terjadi kesalahan, silakan coba lagi.';
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors)[0][0];
                } else if (xhr.status === 429) {
                    msg = 'Terlalu banyak permintaan. Coba lagi sebentar lagi.';
                }
                toast(msg, 'danger');
                done($btn, label);
            });
        };
    })();
</script>
