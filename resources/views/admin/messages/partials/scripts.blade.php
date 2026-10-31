<script>
    $(function () {
        var $composeModal = $('#composeModal');
        var $editMessageModal = $('#editMessageModal');
        var $editMessageForm = $('#editMessageForm');
        var $editMessageContent = $('#editMessageContent');
        var $recipientSelect = $('.js-message-recipients');
        var $messageInput = $('#messageInput');
        var $messageImageInputs = $('.js-message-image-input');
        var $chatMessages = $('#chatMessages');
        var $messagesShell = $('.messages-shell');
        var contentWrapper = document.querySelector('.app-main');
        var contentHeader = document.querySelector('.app-content-header');
        var mainFooter = document.querySelector('.app-footer');
        var viewportFrame = null;
        var hasTopAnchor = window.location.search.indexOf('anchor=top') !== -1;

        function showModal(selector) {
            var el = document.querySelector(selector);
            if (el) {
                bootstrap.Modal.getOrCreateInstance(el).show();
            }
        }

        if ($recipientSelect.length) {
            $recipientSelect.select2({
                dropdownParent: $composeModal,
                width: '100%'
            });
        }

        function autoResize($textarea) {
            if (!$textarea.length) {
                return;
            }

            $textarea.css('height', 'auto');
            $textarea.css('height', Math.min($textarea[0].scrollHeight, 160) + 'px');
        }

        function getImagePreview($input) {
            return $($input.attr('data-preview'));
        }

        function clearImagePreview($preview) {
            var objectUrl = $preview.data('objectUrl');

            if (objectUrl && window.URL && window.URL.revokeObjectURL) {
                window.URL.revokeObjectURL(objectUrl);
            }

            $preview.removeData('objectUrl');
            $preview.find('.messages-image-preview-thumb').attr('src', '');
            $preview.find('.messages-image-preview-name').text('');
            $preview.attr('hidden', 'hidden');
        }

        function updateImagePreview($input) {
            var $preview = getImagePreview($input);
            var input = $input.get(0);
            var file = input && input.files ? input.files[0] : null;

            if (!$preview.length) {
                return;
            }

            clearImagePreview($preview);

            if (!file || !window.URL || !window.URL.createObjectURL) {
                return;
            }

            var objectUrl = window.URL.createObjectURL(file);
            $preview.find('.messages-image-preview-thumb').attr('src', objectUrl);
            $preview.find('.messages-image-preview-name').text(file.name);
            $preview.data('objectUrl', objectUrl).removeAttr('hidden');
        }

        function openEditMessageModal($trigger, content) {
            if (!$editMessageModal.length || !$editMessageForm.length) {
                return;
            }

            var modalEl = $editMessageModal[0];

            $editMessageForm.attr('action', $trigger.attr('data-message-url'));
            $editMessageContent.val(typeof content === 'string' ? content : $trigger.attr('data-message-content'));

            modalEl.addEventListener('shown.bs.modal', function () {
                autoResize($editMessageContent);
                $editMessageContent.trigger('focus');
            }, { once: true });

            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        function setMessageImageState($image, hasError) {
            var $container = $image.closest('.js-message-image-container');
            var $retryButton = $container.find('.js-message-image-retry');

            $container.toggleClass('has-error', hasError);
            $container.find('.messages-image-error').prop('hidden', !hasError);
            $retryButton.prop('disabled', false);
        }

        function retryMessageImage($container) {
            var $image = $container.find('.js-message-image');
            var imageUrl = $image.attr('data-image-url');

            if (!imageUrl) {
                return;
            }

            $container.removeClass('has-error');
            $container.find('.messages-image-error').prop('hidden', true);
            $container.find('.js-message-image-retry').prop('disabled', true);
            $image.attr('src', imageUrl + (imageUrl.indexOf('?') === -1 ? '?' : '&') + 'retry=' + Date.now());
        }

        function updateMessageWorkspaceHeight() {
            if (!contentWrapper || !$messagesShell.length) {
                return;
            }

            var wrapperTop = Math.max(0, contentWrapper.getBoundingClientRect().top);
            var footerHeight = mainFooter ? mainFooter.getBoundingClientRect().height : 0;
            var workspaceHeight = Math.max(0, window.innerHeight - wrapperTop - footerHeight);

            contentWrapper.style.setProperty('--messages-workspace-height', workspaceHeight + 'px');

            var viewportBottom = window.innerHeight;

            if (window.visualViewport) {
                viewportBottom = window.visualViewport.offsetTop + window.visualViewport.height;
            }

            $messagesShell.each(function () {
                var shellTop = this.getBoundingClientRect().top;
                var visibleHeight = Math.max(0, viewportBottom - shellTop - 8);

                this.style.setProperty('--messages-visible-max-height', visibleHeight + 'px');
            });
        }

        function scheduleMessageWorkspaceUpdate() {
            if (viewportFrame !== null) {
                window.cancelAnimationFrame(viewportFrame);
            }

            viewportFrame = window.requestAnimationFrame(function () {
                viewportFrame = null;
                updateMessageWorkspaceHeight();
            });
        }

        updateMessageWorkspaceHeight();
        $(window).on('resize orientationchange', scheduleMessageWorkspaceUpdate);

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', scheduleMessageWorkspaceUpdate);
            window.visualViewport.addEventListener('scroll', scheduleMessageWorkspaceUpdate);
        }

        if (window.ResizeObserver) {
            var workspaceObserver = new ResizeObserver(scheduleMessageWorkspaceUpdate);

            if (contentHeader) {
                workspaceObserver.observe(contentHeader);
            }

            if (mainFooter) {
                workspaceObserver.observe(mainFooter);
            }
        }

        if ($messageInput.length) {
            autoResize($messageInput);
            $messageInput.on('input', function () {
                autoResize($(this));
                scheduleMessageWorkspaceUpdate();
            });
            $messageInput.on('focus', scheduleMessageWorkspaceUpdate);
        }

        if ($editMessageContent.length) {
            $editMessageContent.on('input', function () {
                autoResize($(this));
            });
        }

        $messageImageInputs.on('change', function () {
            updateImagePreview($(this));
        });

        $('.js-message-image-remove').on('click', function () {
            var $preview = $(this).closest('.messages-image-preview');
            var $input = $($preview.attr('data-input'));

            if ($input.length) {
                $input.val('');
            }

            clearImagePreview($preview);
        });

        $('.js-message-edit-trigger').on('click', function () {
            openEditMessageModal($(this));
        });

        $('.js-message-image')
            .on('load', function () {
                setMessageImageState($(this), false);
            })
            .on('error', function () {
                setMessageImageState($(this), true);
            })
            .each(function () {
                // A deferred lazy image can be "complete" with no decoded width
                // before the browser starts fetching it. Only restore already-loaded
                // images here; the error event remains the source of truth for failures.
                if (this.complete && this.naturalWidth > 0) {
                    setMessageImageState($(this), false);
                }
            });

        $('.js-message-image-retry').on('click', function () {
            retryMessageImage($(this).closest('.js-message-image-container'));
        });

        function confirmMessageAction($form, options) {
            if ($form.data('confirmed')) {
                return true;
            }

            var $submitButtons = $form.find('button[type="submit"]');

            if (typeof Swal === 'undefined') {
                return true;
            }

            Swal.fire({
                title: options.title,
                text: options.text,
                icon: options.icon || 'warning',
                showCancelButton: true,
                confirmButtonText: options.confirmButtonText,
                cancelButtonText: 'Cancel',
                confirmButtonColor: options.confirmButtonColor || '#dc3545',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    $form.data('confirmed', true);
                    $form.trigger('submit');
                } else {
                    $submitButtons.prop('disabled', false);
                    $submitButtons.find('.fa').removeClass('fa-spin');
                }
            });

            return false;
        }

        $('.js-message-unsend-form').on('submit', function (event) {
            if (!confirmMessageAction($(this), {
                title: 'Unsend message?',
                text: 'Everyone will see a placeholder instead.',
                confirmButtonText: 'Unsend'
            })) {
                event.preventDefault();
            }
        });

        $('#removeGroupAvatarForm').on('submit', function (event) {
            if (!confirmMessageAction($(this), {
                title: 'Remove group avatar?',
                text: 'The default group icon will be shown.',
                confirmButtonText: 'Remove'
            })) {
                event.preventDefault();
            }
        });

        @if ($errors->has('group_avatar'))
            showModal('#groupAvatarModal');
        @endif

        @if ($errors->has('mesg_group_name'))
            showModal('#renameGroupModal');
        @endif

            @if (session('editMessageId'))
                var $failedEditTrigger = $('.js-message-edit-trigger').filter(function () {
                    return String($(this).attr('data-message-id')) === String({{ (int) session('editMessageId') }});
                }).first();
                var failedEditContent = @json(old('mes_content'));

                if ($failedEditTrigger.length) {
                    openEditMessageModal($failedEditTrigger, failedEditContent);
                }
            @endif

        if ($chatMessages.length && !hasTopAnchor) {
            $chatMessages.scrollTop($chatMessages[0].scrollHeight);
        }
    });
</script>