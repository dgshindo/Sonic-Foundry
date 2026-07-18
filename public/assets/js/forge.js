(() => {
    'use strict';

    /*
    |--------------------------------------------------------------------------
    | Forge DOM
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

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    let busy = false;

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
    | Composer state
    |--------------------------------------------------------------------------
    */

    const setComposerState = (
        isBusy,
        phase = 'idle'
    ) => {
        busy = isBusy;

        textarea.readOnly = isBusy;
        sendButton.disabled = isBusy;

        form.setAttribute(
            'aria-busy',
            isBusy ? 'true' : 'false'
        );

        if (!isBusy) {
            sendButton.textContent = 'Send';
            notice.textContent = defaultNotice;

            return;
        }

        if (phase === 'saving') {
            sendButton.textContent = 'Saving...';

            notice.textContent =
                'Saving your message to this Work...';

            return;
        }

        if (phase === 'thinking') {
            sendButton.textContent = 'Thinking...';

            notice.textContent =
                'The Creative Partner is considering your message...';

            return;
        }

        sendButton.textContent = 'Working...';

        notice.textContent =
            'The Forge is processing your request...';
    };

    /*
    |--------------------------------------------------------------------------
    | Scrolling
    |--------------------------------------------------------------------------
    */

    const scrollConversationToBottom = (
        behavior = 'smooth'
    ) => {
        history.scrollTo({
            top: history.scrollHeight,
            behavior
        });
    };

    /*
    |--------------------------------------------------------------------------
    | Empty state
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

    /*
    |--------------------------------------------------------------------------
    | Message construction
    |--------------------------------------------------------------------------
    */

    const createMessageCard = ({
        role,
        author,
        createdAt = '',
        displayTime = '',
        messageId = null
    }) => {
        const article = document.createElement(
            'article'
        );

        article.classList.add(
            'forge-message',
            role === 'user'
                ? 'forge-message--user'
                : 'forge-message--partner'
        );

        if (messageId !== null) {
            article.dataset.messageId = String(
                messageId
            );
        }

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

        identity.textContent = author;

        const metadata = document.createElement(
            createdAt !== ''
                ? 'time'
                : 'span'
        );

        metadata.className =
            'forge-message__role';

        if (
            metadata instanceof HTMLTimeElement
            && createdAt !== ''
        ) {
            metadata.dateTime = createdAt;
        }

        metadata.textContent = displayTime;

        messageHeader.append(
            identity,
            metadata
        );

        const body = document.createElement(
            'div'
        );

        body.className =
            'forge-message__body';

        const paragraph = document.createElement(
            'p'
        );

        body.append(paragraph);

        article.append(
            messageHeader,
            body
        );

        return {
            article,
            paragraph,
            metadata
        };
    };

    const appendUserMessage = (message) => {
        const card = createMessageCard({
            role: 'user',
            author: message.author,
            createdAt: message.createdAt,
            displayTime: message.displayTime,
            messageId: message.id
        });

        /*
         * User-controlled content must always use textContent.
         */
        card.paragraph.textContent =
            message.content;

        removeEmptyState();

        history.append(
            card.article
        );

        scrollConversationToBottom();

        return card.article;
    };

    const appendPendingPartnerMessage = () => {
        const card = createMessageCard({
            role: 'partner',
            author: 'Creative Partner',
            displayTime: 'Responding...'
        });

        card.article.classList.add(
            'forge-message--streaming'
        );

        card.article.setAttribute(
            'aria-busy',
            'true'
        );

        card.paragraph.textContent = '';

        history.append(
            card.article
        );

        scrollConversationToBottom();

        return card;
    };

    const finalizePartnerMessage = (
        card,
        message
    ) => {
        card.article.classList.remove(
            'forge-message--streaming'
        );

        card.article.removeAttribute(
            'aria-busy'
        );

        card.article.dataset.messageId =
            String(message.id);

        if (
            card.metadata
            instanceof HTMLTimeElement
        ) {
            card.metadata.dateTime =
                message.createdAt;
        } else {
            const time = document.createElement(
                'time'
            );

            time.className =
                'forge-message__role';

            time.dateTime =
                message.createdAt;

            card.metadata.replaceWith(time);

            card.metadata = time;
        }

        card.metadata.textContent =
            message.displayTime;

        /*
         * The completed server value is authoritative.
         */
        card.paragraph.textContent =
            message.content;

        scrollConversationToBottom();
    };

    const removePendingPartnerMessage = (
        card
    ) => {
        if (
            card?.article
            && card.article.isConnected
        ) {
            card.article.remove();
        }
    };

    /*
    |--------------------------------------------------------------------------
    | JSON response parsing
    |--------------------------------------------------------------------------
    */

    const readJsonResponse = async (
        response
    ) => {
        const responseText =
            await response.text();

        if (responseText.trim() === '') {
            throw new Error(
                'The Forge returned an empty response.'
            );
        }

        try {
            return JSON.parse(
                responseText
            );
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
    | Save creator message
    |--------------------------------------------------------------------------
    */

    const saveCreatorMessage = async (
        formData
    ) => {
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

        const payload =
            await readJsonResponse(response);

        if (response.status === 401) {
            window.location.assign(
                '/login.php'
            );

            throw new Error(
                'Authentication required.'
            );
        }

        if (
            !response.ok
            || payload.success !== true
            || !payload.data?.message
        ) {
            throw new Error(
                payload.error?.message
                ?? 'Your message could not be saved.'
            );
        }

        return payload.data.message;
    };

    /*
    |--------------------------------------------------------------------------
    | Streaming request helpers
    |--------------------------------------------------------------------------
    */

    const createResponseFormData = () => {
        const responseData = new FormData();

        const csrfInput = form.elements.namedItem(
            'csrf_token'
        );

        const workInput = form.elements.namedItem(
            'work_id'
        );

        const pillarInput = form.elements.namedItem(
            'pillar'
        );

        if (
            !(csrfInput instanceof HTMLInputElement)
            || !(workInput instanceof HTMLInputElement)
            || !(pillarInput instanceof HTMLInputElement)
        ) {
            throw new Error(
                'The Forge response request is missing required context.'
            );
        }

        responseData.set(
            'csrf_token',
            csrfInput.value
        );

        responseData.set(
            'work_id',
            workInput.value
        );

        responseData.set(
            'pillar',
            pillarInput.value
        );

        return responseData;
    };

    const readErrorPayload = async (
        response
    ) => {
        try {
            const payload =
                await response.json();

            return payload.error?.message
                ?? 'The Creative Partner could not respond.';
        } catch {
            return (
                'The Creative Partner could not respond.'
            );
        }
    };

    /*
    |--------------------------------------------------------------------------
    | NDJSON stream processing
    |--------------------------------------------------------------------------
    */

    const processStreamEvent = (
        event,
        card,
        streamState
    ) => {
        if (
            !event
            || typeof event !== 'object'
            || typeof event.type !== 'string'
        ) {
            return;
        }

        switch (event.type) {
            case 'response.started':
                card.metadata.textContent =
                    'Responding...';

                break;

            case 'response.delta':
                if (
                    typeof event.delta !== 'string'
                ) {
                    break;
                }

                streamState.receivedText +=
                    event.delta;

                /*
                 * textContent prevents streamed HTML execution.
                 */
                card.paragraph.textContent =
                    streamState.receivedText;

                scrollConversationToBottom(
                    'auto'
                );

                break;

            case 'response.completed':
                if (
                    !event.message
                    || typeof event.message !== 'object'
                ) {
                    throw new Error(
                        'The Creative Partner response completed without a saved message.'
                    );
                }

                streamState.completed = true;

                finalizePartnerMessage(
                    card,
                    event.message
                );

                break;

            case 'response.error':
                console.error(
                    'Creative Partner stream error:',
                    event.error
                );

                throw new Error(
                    event.error?.detail
                    ?? event.error?.message
                    ?? 'The Creative Partner could not respond.'
                );

            default:
                /*
                 * Unknown future events are safely ignored.
                 */
                break;
        }
    };

    const consumeNdjsonStream = async (
        response,
        card
    ) => {
        if (!response.body) {
            throw new Error(
                'Streaming is not supported by this browser response.'
            );
        }

        const reader =
            response.body.getReader();

        const decoder =
            new TextDecoder('utf-8');

        const streamState = {
            buffer: '',
            receivedText: '',
            completed: false
        };

        while (true) {
            const {
                value,
                done
            } = await reader.read();

            if (value) {
                streamState.buffer +=
                    decoder.decode(
                        value,
                        {
                            stream: !done
                        }
                    );
            }

            let newlineIndex;

            while (
                (
                    newlineIndex =
                        streamState.buffer.indexOf(
                            '\n'
                        )
                ) !== -1
            ) {
                const line = streamState.buffer
                    .slice(0, newlineIndex)
                    .trim();

                streamState.buffer =
                    streamState.buffer.slice(
                        newlineIndex + 1
                    );

                if (line === '') {
                    continue;
                }

                let event;

                try {
                    event = JSON.parse(line);
                } catch {
                    console.error(
                        'Unreadable Forge stream event:',
                        line
                    );

                    throw new Error(
                        'The Creative Partner returned an unreadable stream event.'
                    );
                }

                processStreamEvent(
                    event,
                    card,
                    streamState
                );
            }

            if (done) {
                break;
            }
        }

        const finalLine =
            streamState.buffer.trim();

        if (finalLine !== '') {
            let finalEvent;

            try {
                finalEvent =
                    JSON.parse(finalLine);
            } catch {
                throw new Error(
                    'The Creative Partner returned an incomplete stream event.'
                );
            }

            processStreamEvent(
                finalEvent,
                card,
                streamState
            );
        }

        if (!streamState.completed) {
            throw new Error(
                'The Creative Partner response ended before it was saved.'
            );
        }
    };

    /*
    |--------------------------------------------------------------------------
    | Request Creative Partner response
    |--------------------------------------------------------------------------
    */

    const streamCreativePartnerResponse =
        async () => {
            const pendingCard =
                appendPendingPartnerMessage();

            try {
                const response = await fetch(
                    '/forge/respond.php',
                    {
                        method: 'POST',

                        body:
                            createResponseFormData(),

                        headers: {
                            'Accept':
                                'application/x-ndjson',

                            'X-Requested-With':
                                'XMLHttpRequest'
                        },

                        credentials:
                            'same-origin',

                        cache:
                            'no-store',

                        redirect:
                            'follow'
                    }
                );

                if (response.status === 401) {
                    window.location.assign(
                        '/login.php'
                    );

                    return;
                }

                if (!response.ok) {
                    throw new Error(
                        await readErrorPayload(
                            response
                        )
                    );
                }

                await consumeNdjsonStream(
                    response,
                    pendingCard
                );
            } catch (error) {
                removePendingPartnerMessage(
                    pendingCard
                );

                throw error;
            }
        };

    /*
    |--------------------------------------------------------------------------
    | Complete conversational submission
    |--------------------------------------------------------------------------
    */

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            if (busy) {
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
             * Capture all form values before changing field state.
             */
            const creatorFormData =
                new FormData(form);

            clearError();

            setComposerState(
                true,
                'saving'
            );

            try {
                const savedCreatorMessage =
                    await saveCreatorMessage(
                        creatorFormData
                    );

                appendUserMessage(
                    savedCreatorMessage
                );

                /*
                 * Once the creator message is persisted, clearing the
                 * composer is safe even if partner generation fails.
                 */
                textarea.value = '';

                setComposerState(
                    true,
                    'thinking'
                );

                await streamCreativePartnerResponse();
            } catch (error) {
                const message =
                    error instanceof Error
                        ? error.message
                        : (
                            'The Forge could not complete '
                            + 'the conversation request.'
                        );

                /*
                 * Authentication redirects intentionally interrupt
                 * execution and do not require an inline message.
                 */
                if (
                    message !==
                    'Authentication required.'
                ) {
                    showError(message);
                }
            } finally {
                setComposerState(false);

                textarea.focus();
            }
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Keyboard shortcut
    |--------------------------------------------------------------------------
    |
    | Enter inserts a line break.
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
    | Initial scroll position
    |--------------------------------------------------------------------------
    */

    window.requestAnimationFrame(() => {
        scrollConversationToBottom(
            'auto'
        );
    });
})();