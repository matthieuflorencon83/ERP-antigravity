/**
 * assets/js/notifications.js
 * Gestion du polling et affichage des notifications
 */

$(document).ready(function () {

    // Config
    const POLL_INTERVAL = 30000; // 30 sec
    let lastPollTime = new Date().toISOString();

    // Init Polling
    pollNotifications();
    setInterval(pollNotifications, POLL_INTERVAL);

    // Mark Read on Link Click (delegation for dropdown items)
    $(document).on('click', '.notification-link', function (e) {
        const notifId = $(this).data('id');
        if (notifId) {
            markAsRead(notifId);
        }
    });

    function pollNotifications() {
        $.ajax({
            url: 'api/notifications.php',
            data: { action: 'poll', since: lastPollTime },
            dataType: 'json',
            success: function (data) {
                if (data.error) return;

                // Update Badge
                updateBadge(data.count);

                // Show Toasts for new items
                if (data.new_notifications && data.new_notifications.length > 0) {
                    data.new_notifications.forEach(notif => {
                        showToast(notif);
                    });
                    // Update timestamp to avoid re-showing
                    lastPollTime = data.timestamp;
                }
            }
        });
    }

    function updateBadge(count) {
        const $badge = $('#notification-badge');
        if (count > 0) {
            $badge.text(count).show();
            // Animation pulse
            $badge.addClass('animate__animated animate__pulse');
            setTimeout(() => $badge.removeClass('animate__animated animate__pulse'), 1000);
        } else {
            $badge.hide();
        }
    }

    function showToast(notif) {
        // Create Toast HTML on the fly
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${notif.type} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="10000">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${notif.title}</strong><br>
                        ${notif.message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        // Append to container (create if not exists)
        let $container = $('.toast-container');
        if ($container.length === 0) {
            $('body').append('<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>');
            $container = $('.toast-container');
        }

        const $toastElement = $(toastHtml);
        $container.append($toastElement);
        const toast = new bootstrap.Toast($toastElement[0]);
        toast.show();

        // Mark as read when closed manually ? No, keep strict read logic on click link or specific action.
    }

    function markAsRead(id) {
        $.post('api/notifications.php?action=mark_read', { id: id });
    }
});
