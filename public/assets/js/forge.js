(() => {
    'use strict';

    /*
    |--------------------------------------------------------------------------
    | Forge conversation elements
    |--------------------------------------------------------------------------
    */

    const form = document.getElementById(
        'forge-composer'
    );

    const history = document.getElementById(
        'forge-message-history'
    );

    const textarea = document.getElementById(
        'forge_message'
    );

    const sendButton = document.getElementById(
        'forge-send-button'
    );

    const notice = document.getElementById(
        'forge-composer-notice'
    );

    const errorElement = document.getElementById(
        'forge-error'
    );

    /*
     * Bundle C will provide these exact elements.
     *
     * Until then, this script exits safely if the current Forge
     * markup has not yet been replaced.
     */

    if (
        !form
        || !history
        || !textarea
        || !sendButton
        || !notice
        || !errorElement
    ) {
        return;
    }

    let submitting = false;

    const defaultNotice = notice.textContent.trim();

    /*
    |--------------------------------------------------------------------------
    | Error presentation
    |--------------------------------------------------------------------------
    */

    const showError = (message) => {
        errorElement.textContent = message;
        errorElement.hidden = false;

        errorElement.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    };

    const clearError = () => {
        errorElement.textContent = '';
        errorElement.hidden = true;
    };

    /*
    |--------------------------------------------------------------------------
    | Submission state
    |--------------------------------------------------------------------------
    */

    const setSubmitting = (isSubmitting) => {
        submitting = isSubmitting;

        /*
         * Read-only fields remain part of form submission.
         * Disabled fields do not.
         */

        textarea.readOnly = isSubmitting;
        sendButton.disabled = isSubmitting;

        form.setAttribute(
            'aria-busy',
            isSubmitting ? 'true' : 'false'
        );

        sendButton.textContent = isSubmitting
            ? 'Saving...'
            : 'Send';

        notice.textContent = isSubmitting
            ? 'Saving your message to this Work...'
            : defaultNotice;
    };

    /*
    |--------------------------------------------------------------------------
    | Conversation helpers
    |--------------------------------------------------------------------------
    */

    const removeEmptyState = () => {
        const emptyState = document.getElementById(
            'forge-conversation-empty'
        );

        if (emptyState) {
            emptyState.remove();
        }
    };

    const scrollConversationToBottom = (
        behavior = 'smooth'
    ) => {
        history.scrollTo({
            top: history.scrollHeight,
            behavior
        });
    };

    /**
     * Append a server-confirmed creator message.
     *
     * User content is inserted through textContent only.
     * It must never be passed through innerHTML.
     */
    const appendUserMessage = (message) => {
        const article = document.createElement(
            'article'
        );

        article.classList.add(
            'forge-message',
            'forge-message--user'
        );

        article.dataset.messageId = String(
            message.id
        );

        const messageHeader = document.createElement(
            'header'
        );

        messageHeader.className =
            'forge-message__header';

        const identity = document.createElement(
            'span'
        );

        identity.className =
            'forge-message__identity';

        identity.textContent = message.author;

        const time = document.createElement(
            'time'
        );

        time.className =
            'forge-message__role';

        time.dateTime = message.createdAt;
        time.textContent = message.displayTime;

        messageHeader.append(
            identity,
            time
        );

        const body = document.createElement(
            'div'
        );

        body.className = 'forge-message__body';

        const paragraph = document.createElement(
            'p'
        );

        paragraph.textContent = message.content;

        body.append(paragraph);

        article.append(
            messageHeader,
            body
        );

        removeEmptyState();
        history.append(article);

        scrollConversationToBottom();
    };

    /*
    |--------------------------------------------------------------------------
    | JSON response parsing
    |--------------------------------------------------------------------------
    */

    const readJsonResponse = async (response) => {
        const responseText = await response.text();

        if (responseText.trim() === '') {
            throw new Error(
                'The Forge returned an empty response.'
            );
        }

        try {
            return JSON.parse(responseText);
        } catch {
            console.error(
                'Unreadable Forge response:',
                responseText
            );

            throw new Error(
                'The Forge returned an unreadable response.'
            );
        }
    };

    /*
    |--------------------------------------------------------------------------
    | AJAX form submission
    |--------------------------------------------------------------------------
    */

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            if (submitting) {
                return;
            }

            const messageContent =
                textarea.value.trim();

            if (messageContent === '') {
                showError(
                    'Write a message before sending it.'
                );

                textarea.focus();

                return;
            }

            /*
             * Construct FormData before changing field state.
             */

            const formData = new FormData(form);

            clearError();
            setSubmitting(true);

            try {
                const response = await fetch(
                    form.action,
                    {
                        method: 'POST',
                        body: formData,

                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With':
                                'XMLHttpRequest'
                        },

                        credentials: 'same-origin',

                        cache: 'no-store',

                        redirect: 'follow'
                    }
                );

                const payload = await readJsonResponse(
                    response
                );

                if (response.status === 401) {
                    window.location.assign(
                        '/login.php'
                    );

                    return;
                }

                if (
                    !response.ok
                    || payload.success !== true
                    || !payload.data
                    || !payload.data.message
                ) {
                    throw new Error(
                        payload.error?.message
                        ?? (
                            'Your message could not '
                            + 'be saved.'
                        )
                    );
                }

                appendUserMessage(
                    payload.data.message
                );

                textarea.value = '';
                textarea.focus();
            } catch (error) {
                showError(
                    error instanceof Error
                        ? error.message
                        : (
                            'Your message could not '
                            + 'be saved.'
                        )
                );
            } finally {
                setSubmitting(false);
            }
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Keyboard submission
    |--------------------------------------------------------------------------
    |
    | Enter inserts a normal line break.
    | Ctrl+Enter or Command+Enter submits.
    |
    */

    textarea.addEventListener(
        'keydown',
        (event) => {
            const submitShortcut =
                event.key === 'Enter'
                && (
                    event.ctrlKey
                    || event.metaKey
                );

            if (!submitShortcut) {
                return;
            }

            event.preventDefault();

            form.requestSubmit();
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Initial position
    |--------------------------------------------------------------------------
    */

    window.requestAnimationFrame(() => {
        scrollConversationToBottom('auto');
    });
})();