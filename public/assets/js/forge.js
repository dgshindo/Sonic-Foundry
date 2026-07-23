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
| Creative artifact DOM
|--------------------------------------------------------------------------
*/

const artifactTabs =
    document.querySelectorAll(
        '[data-artifact-tab]'
    );

const artifactPanels =
    document.querySelectorAll(
        '[data-artifact-panel]'
    );

const artifactTabStorageKey =
    'sonic-foundry-active-artifact-tab';

const songStyleGenerateForm =
    document.getElementById(
        'forge-song-style-generate-form'
    );

const songStyleGenerateButton =
    document.getElementById(
        'forge-song-style-generate-button'
    );

const songStyleFeedback =
    document.getElementById(
        'forge-song-style-feedback'
    );

const lyricsGenerateForm =
    document.getElementById(
        'forge-lyrics-generate-form'
    );

const lyricsGenerateButton =
    document.getElementById(
        'forge-lyrics-generate-button'
    );

const lyricsFeedback =
    document.getElementById(
        'forge-lyrics-feedback'
    );

/*
|--------------------------------------------------------------------------
| Creative artifact tabs
|--------------------------------------------------------------------------
*/

const activateArtifactTab = (
    selectedKey
) => {
    const validPanelExists =
        Array.from(
            artifactPanels
        ).some(
            (panel) =>
                panel.dataset.artifactPanel
                === selectedKey
        );

    const activeKey = validPanelExists
        ? selectedKey
        : 'style-guide';

    artifactTabs.forEach((tab) => {
        const isSelected =
            tab.dataset.artifactTab
            === activeKey;

        tab.classList.toggle(
            'is-active',
            isSelected
        );

        tab.setAttribute(
            'aria-selected',
            isSelected
                ? 'true'
                : 'false'
        );

        tab.tabIndex = isSelected
            ? 0
            : -1;
    });

    artifactPanels.forEach((panel) => {
        panel.hidden =
            panel.dataset.artifactPanel
            !== activeKey;
    });
};

artifactTabs.forEach((tab) => {
    tab.addEventListener(
        'click',
        () => {
            const selectedKey =
                tab.dataset.artifactTab;

            if (!selectedKey) {
                return;
            }

            window.sessionStorage.setItem(
                artifactTabStorageKey,
                selectedKey
            );

            activateArtifactTab(
                selectedKey
            );
        }
    );
});

const savedArtifactTab =
    window.sessionStorage.getItem(
        artifactTabStorageKey
    );

activateArtifactTab(
    savedArtifactTab
        ?? 'style-guide'
);

    /*
    |--------------------------------------------------------------------------
    | Song Style generation
    |--------------------------------------------------------------------------
    */

    const generateSongStyle = async () => {
        if (
            !songStyleGenerateForm
            || !songStyleGenerateButton
        ) {
            return;
        }

        const originalButtonText =
            songStyleGenerateButton.textContent;

        songStyleGenerateButton.disabled = true;

        songStyleGenerateButton.textContent =
            'Forging Song Style...';

        if (songStyleFeedback) {
            songStyleFeedback.hidden = false;

            songStyleFeedback.textContent =
                'Interpreting the finished lyrics within the Producer Style Guide...';

            songStyleFeedback.classList.remove(
                'forge-song-style__feedback--error'
            );
        }

        try {
            const response = await fetch(
                songStyleGenerateForm.action,
                {
                    method: 'POST',

                    body: new FormData(
                        songStyleGenerateForm
                    ),

                    headers: {
                        'Accept':
                            'application/json',

                        'X-Requested-With':
                            'XMLHttpRequest'
                    },

                    credentials:
                        'same-origin',

                    cache:
                        'no-store'
                }
            );

            const payload =
                await readJsonResponse(
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
                || !payload.data?.artifact
            ) {
                throw new Error(
                    payload.error?.message
                    ?? (
                        'The Song Style Addendum '
                        + 'could not be generated.'
                    )
                );
            }
            window.sessionStorage.setItem(
                artifactTabStorageKey,
                'song-style'
            );
            window.location.reload();
        } catch (error) {
            if (songStyleFeedback) {
                songStyleFeedback.textContent =
                    error instanceof Error
                        ? error.message
                        : (
                            'The Song Style Addendum '
                            + 'could not be generated.'
                        );

                songStyleFeedback.hidden = false;

                songStyleFeedback.classList.add(
                    'forge-song-style__feedback--error'
                );
            }
        } finally {
            if (
                songStyleGenerateButton
                && songStyleGenerateButton.isConnected
            ) {
                songStyleGenerateButton.disabled =
                    false;

                songStyleGenerateButton.textContent =
                    originalButtonText;
            }
        }
    };

    if (songStyleGenerateForm) {
        songStyleGenerateForm.addEventListener(
            'submit',
            (event) => {
                event.preventDefault();

                void generateSongStyle();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Creative Memory DOM
    |--------------------------------------------------------------------------
    */

    const memoryPanel =
        document.getElementById(
            'forge-memory-panel'
        );

    const memoryStatus =
        document.getElementById(
            'forge-memory-status'
        );

    const memoryFeedback =
        document.getElementById(
            'forge-memory-feedback'
        );

    const memoryConfirmForm =
        document.getElementById(
            'forge-memory-confirm-form'
        );

    const memoryConfirmButton =
        document.getElementById(
            'forge-memory-confirm-button'
        );

    const memoryExtractForm =
        document.getElementById(
            'forge-memory-extract-form'
        );

    const memoryExtractButton =
        document.getElementById(
            'forge-memory-extract-button'
        );

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
            | Creative Memory extraction
            |--------------------------------------------------------------------------
            */

            const extractMemory = async () => {
                if (
                    !memoryExtractForm
                    || !memoryExtractButton
                ) {
                    return;
                }

                const originalButtonText =
                    memoryExtractButton.textContent;

                memoryExtractButton.disabled = true;

                memoryExtractButton.textContent =
                    'Building understanding...';

                if (memoryFeedback) {
                    memoryFeedback.hidden = true;
                    memoryFeedback.textContent = '';

                    memoryFeedback.classList.remove(
                        'forge-memory__feedback--error'
                    );
                }

                try {
                    const response = await fetch(
                        memoryExtractForm.action,
                        {
                            method: 'POST',

                            body: new FormData(
                                memoryExtractForm
                            ),

                            headers: {
                                'Accept':
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest'
                            },

                            credentials:
                                'same-origin',

                            cache:
                                'no-store'
                        }
                    );

                    const payload =
                        await readJsonResponse(
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
                        || !payload.data?.memory
                    ) {
                        throw new Error(
                            payload.error?.message
                            ?? (
                                'The Creative Partner could '
                                + 'not propose an understanding.'
                            )
                        );
                    }

                    /*
                    * Use the proven server renderer for the complete
                    * proposed-memory state and confirmation controls.
                    */
                    window.location.reload();
                } catch (error) {
                    if (memoryFeedback) {
                        memoryFeedback.textContent =
                            error instanceof Error
                                ? error.message
                                : (
                                    'The Creative Partner could '
                                    + 'not propose an understanding.'
                                );

                        memoryFeedback.hidden = false;

                        memoryFeedback.classList.add(
                            'forge-memory__feedback--error'
                        );
                    }
                } finally {
                    if (
                        memoryExtractButton
                        && memoryExtractButton.isConnected
                    ) {
                        memoryExtractButton.disabled =
                            false;

                        memoryExtractButton.textContent =
                            originalButtonText;
                    }
                }
            };

            if (memoryExtractForm) {
                memoryExtractForm.addEventListener(
                    'submit',
                    (event) => {
                        event.preventDefault();

                        void extractMemory();
                    }
                );
            }
    
        /*
        |--------------------------------------------------------------------------
        | Creative Memory confirmation
        |--------------------------------------------------------------------------
        */

        const memoryField = (name) => {
            return document.querySelector(
                `[data-memory-field="${name}"]`
            );
        };

        const replaceMemoryText = (
            fieldName,
            field,
            fallback
        ) => {
            const container = memoryField(
                fieldName
            );

            if (!container) {
                return;
            }

            container.replaceChildren();

            const paragraph = document.createElement(
                'p'
            );

            paragraph.textContent =
                field?.display
                ?? fallback;

            container.append(
                paragraph
            );

            container.className = field?.hasValue
                ? 'forge-memory__value'
                : 'forge-memory-empty';
        };

        const replaceMemoryTags = (
            fieldName,
            field,
            fallback
        ) => {
            const container = memoryField(
                fieldName
            );

            if (!container) {
                return;
            }

            container.replaceChildren();

            const values = Array.isArray(
                field?.values
            )
                ? field.values
                : [];

            if (values.length === 0) {
                const empty = document.createElement(
                    'div'
                );

                empty.className =
                    'forge-memory-empty';

                const paragraph =
                    document.createElement('p');

                paragraph.textContent =
                    field?.display
                    ?? fallback;

                empty.append(
                    paragraph
                );

                container.append(
                    empty
                );

                return;
            }

            const list = document.createElement(
                'ul'
            );

            list.className =
                'forge-memory-tags';

            for (const value of values) {
                const item = document.createElement(
                    'li'
                );

                item.textContent =
                    String(value);

                list.append(
                    item
                );
            }

            container.append(
                list
            );
        };

            const refreshMemoryPanel = (
        memory
    ) => {
        if (
            !memoryPanel
            || !memoryStatus
            || !memory
        ) {
            return;
        }

        memoryPanel.dataset.memoryStatus =
            memory.status.value;

        memoryStatus.className =
            'forge-memory__status '
            + (
                'forge-memory__status--'
                + memory.status.value
            );

        memoryStatus.textContent =
            memory.status.label;

        const sections = Array.isArray(
            memory.sections
        )
            ? memory.sections
            : [];

        for (const section of sections) {
            if (
                !section
                || typeof section !== 'object'
                || typeof section.key !== 'string'
            ) {
                continue;
            }

            const field = (
                section.value
                && typeof section.value === 'object'
            )
                ? section.value
                : {};

            if (section.type === 'list') {
                replaceMemoryTags(
                    section.key,
                    field,
                    'Not yet established.'
                );

                continue;
            }

            replaceMemoryText(
                section.key,
                field,
                'Not yet established.'
            );
        }

        if (memoryMeta) {
            const revision =
                memory.revision ?? '—';

            const updated =
                memory.updatedAt?.display
                ?? '—';

            memoryMeta.textContent =
                `Revision ${revision} · Updated ${updated}`;
        }

        if (memoryConfirmForm) {
            memoryConfirmForm.remove();
        }

        if (memoryFeedback) {
            memoryFeedback.classList.remove(
                'forge-memory__feedback--error'
            );

            memoryFeedback.textContent =
                'Creative Memory confirmed.';

            memoryFeedback.hidden = false;
        }
    };

        const confirmMemory = async () => {
            if (
                !memoryConfirmForm
                || !memoryConfirmButton
            ) {
                return;
            }

            memoryConfirmButton.disabled = true;

            memoryConfirmButton.textContent = 'Confirming and evaluating...';

            if (memoryFeedback) {
                memoryFeedback.hidden = true;
                memoryFeedback.textContent = '';

                memoryFeedback.classList.remove(
                    'forge-memory__feedback--error'
                );
            }

            try {
                const response = await fetch(
                    memoryConfirmForm.action,
                    {
                        method: 'POST',

                        body: new FormData(
                            memoryConfirmForm
                        ),

                        headers: {
                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest'
                        },

                        credentials:
                            'same-origin',

                        cache:
                            'no-store'
                    }
                );

                const payload =
                    await readJsonResponse(
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
                    || !payload.data?.memory
                ) {
                    throw new Error(
                        payload.error?.message
                        ?? (
                            'Creative Memory could not '
                            + 'be confirmed.'
                        )
                    );
                }

                refreshMemoryPanel(
                    payload.data.memory
                );
                if (payload.data.progressError) {
                    if (memoryFeedback) {
                        memoryFeedback.textContent =
                            payload.data.progressError;

                        memoryFeedback.hidden = false;

                        memoryFeedback.classList.add(
                            'forge-memory__feedback--error'
                        );
                    }

                    return;
                }

                window.location.reload();
            } catch (error) {
                if (memoryFeedback) {
                    memoryFeedback.textContent =
                        error instanceof Error
                            ? error.message
                            : (
                                'Creative Memory could '
                                + 'not be confirmed.'
                            );

                    memoryFeedback.hidden = false;

                    memoryFeedback.classList.add(
                        'forge-memory__feedback--error'
                    );
                }
            } finally {
                if (
                    memoryConfirmButton
                    && memoryConfirmButton.isConnected
                ) {
                    memoryConfirmButton.disabled = false;

                    memoryConfirmButton.textContent =
                        'Confirm Understanding';
                }
            }
        };

           

            

        if (memoryConfirmForm) {
            memoryConfirmForm.addEventListener(
                'submit',
                (event) => {
                    event.preventDefault();

                    void confirmMemory();
                }
            );
        }
    
    /*
    |--------------------------------------------------------------------------
    | Workflow DOM
    |--------------------------------------------------------------------------
    */

    const workflowCompleteForm = document.getElementById(
        'forge-workflow-complete-form'
    );

    const workflowCompleteButton = document.getElementById(
        'forge-workflow-complete-button'
    );

    const workflowAction = document.getElementById(
        'forge-workflow-action'
    );

    const workflowFeedback = document.getElementById(
        'forge-workflow-feedback'
    );

    const activeWorkflowStatus = document.getElementById(
        'forge-active-workflow-status'
    );

        /*
    |--------------------------------------------------------------------------
    | Pillar workflow completion
    |--------------------------------------------------------------------------
    */

    const workflowLink = (pillar) => {
        return document.querySelector(
            `[data-pillar-link][data-pillar="${pillar}"]`
        );
    };

    const updateWorkflowNavigation = (
        workflow
    ) => {
        if (!Array.isArray(workflow)) {
            return;
        }

        for (const item of workflow) {
            const link = workflowLink(
                item.pillar.value
            );

            if (!link) {
                continue;
            }

            link.classList.remove(
                'forge-pillar-link--locked',
                'forge-pillar-link--available',
                'forge-pillar-link--completed'
            );

            link.classList.add(
                `forge-pillar-link--${item.status.value}`
            );

            link.dataset.workflowStatus =
                item.status.value;

            const marker = link.querySelector(
                '.forge-pillar-link__marker'
            );

            const status = link.querySelector(
                '.forge-pillar-link__status'
            );

            if (marker) {
                marker.textContent = item.isCompleted
                    ? '✓'
                    : (
                        link.matches(
                            '[aria-current="step"]'
                        )
                            ? '●'
                            : '○'
                    );
            }

            if (status) {
                status.textContent =
                    item.status.label;
            }

            if (item.isLocked) {
                link.removeAttribute('href');
                link.setAttribute(
                    'aria-disabled',
                    'true'
                );
                link.tabIndex = -1;
            } else {
                link.href =
                    `/forge.php?work=${link.dataset.workId}`
                    + `&pillar=${encodeURIComponent(
                        item.pillar.value
                    )}`;

                link.removeAttribute(
                    'aria-disabled'
                );

                link.removeAttribute(
                    'tabindex'
                );
            }
        }
    };

    const renderCompletedWorkflow = (
        completed,
        workflow
    ) => {
        updateWorkflowNavigation(
            workflow
        );

        if (activeWorkflowStatus) {
            activeWorkflowStatus.textContent =
                completed.status.label;
        }

        if (workflowAction) {
            workflowAction.replaceChildren();

            const completedState =
                document.createElement('div');

            completedState.className =
                'forge-workflow-action__completed';

            const heading =
                document.createElement('strong');

            heading.textContent =
                `${completed.pillar.label} Complete`;

            completedState.append(
                heading
            );

            if (completed.completedAt?.display) {
                const date =
                    document.createElement('span');

                date.textContent =
                    `Completed ${completed.completedAt.display}`;

                completedState.append(
                    date
                );
            }

            workflowAction.append(
                completedState
            );

            const nextPillar = workflow.find(
                (item) => item.isAvailable
                    && item.pillar.value
                    !== completed.pillar.value
            );

            if (nextPillar) {
                const continueLink =
                    document.createElement('a');

                continueLink.className =
                    'button button--primary';

                continueLink.id =
                    'forge-next-pillar-link';

                continueLink.href =
                    `/forge.php?work=${completed.workId}`
                    + `&pillar=${encodeURIComponent(
                        nextPillar.pillar.value
                    )}`;

                continueLink.textContent =
                    `Continue to ${nextPillar.pillar.label}`;

                workflowAction.append(
                    continueLink
                );
            }
        }

        if (workflowFeedback) {
            workflowFeedback.classList.remove(
                'forge-workflow-feedback--error'
            );

            workflowFeedback.textContent =
                `${completed.pillar.label} is complete. `
                + 'The next pillar is now available.';

            workflowFeedback.hidden = false;
        }
    };

    const completeWorkflow = async () => {
        if (
            !workflowCompleteForm
            || !workflowCompleteButton
        ) {
            return;
        }

        workflowCompleteButton.disabled = true;

        workflowCompleteButton.textContent =
            'Completing...';

        if (workflowFeedback) {
            workflowFeedback.hidden = true;
            workflowFeedback.textContent = '';

            workflowFeedback.classList.remove(
                'forge-workflow-feedback--error'
            );
        }

        try {
            const response = await fetch(
                workflowCompleteForm.action,
                {
                    method: 'POST',

                    body: new FormData(
                        workflowCompleteForm
                    ),

                    headers: {
                        'Accept':
                            'application/json',

                        'X-Requested-With':
                            'XMLHttpRequest'
                    },

                    credentials:
                        'same-origin',

                    cache:
                        'no-store'
                }
            );

            const payload =
                await readJsonResponse(
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
                || !payload.data?.completed
                || !Array.isArray(
                    payload.data.workflow
                )
            ) {
                throw new Error(
                    payload.error?.message
                    ?? 'The pillar could not be completed.'
                );
            }

            renderCompletedWorkflow(
                payload.data.completed,
                payload.data.workflow
            );
        } catch (error) {
            if (workflowFeedback) {
                workflowFeedback.textContent =
                    error instanceof Error
                        ? error.message
                        : (
                            'The pillar could not '
                            + 'be completed.'
                        );

                workflowFeedback.hidden = false;

                workflowFeedback.classList.add(
                    'forge-workflow-feedback--error'
                );
            }
        } finally {
            if (
                workflowCompleteButton
                && workflowCompleteButton.isConnected
            ) {
                workflowCompleteButton.disabled =
                    false;

                workflowCompleteButton.textContent =
                    'Complete Story';
            }
        }
    };

    if (workflowCompleteForm) {
        workflowCompleteForm.addEventListener(
            'submit',
            (event) => {
                event.preventDefault();

                void completeWorkflow();
            }
        );
    }

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

    /*
    |--------------------------------------------------------------------------
    | Music Style Generation Prompt
    |--------------------------------------------------------------------------
    */

    const generateMusicPrompt = async () => {
        if (
            !musicPromptGenerateForm
            || !musicPromptGenerateButton
        ) {
            return;
        }

        const originalButtonText =
            musicPromptGenerateButton.textContent;

        musicPromptGenerateButton.disabled = true;

        musicPromptGenerateButton.textContent =
            'Forging Music Prompt...';

        if (musicPromptFeedback) {
            musicPromptFeedback.hidden = false;

            musicPromptFeedback.textContent =
                'Compressing the approved production direction into 1,000 characters or fewer...';

            musicPromptFeedback.classList.remove(
                'forge-music-prompt__feedback--error'
            );
        }

        try {
            const response = await fetch(
                musicPromptGenerateForm.action,
                {
                    method: 'POST',

                    body: new FormData(
                        musicPromptGenerateForm
                    ),

                    headers: {
                        'Accept':
                            'application/json',

                        'X-Requested-With':
                            'XMLHttpRequest'
                    },

                    credentials:
                        'same-origin',

                    cache:
                        'no-store'
                }
            );

            const payload =
                await readJsonResponse(
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
                || !payload.data?.artifact
            ) {
                throw new Error(
                    payload.error?.message
                    ?? (
                        'The Music Style Generation '
                        + 'Prompt could not be generated.'
                    )
                );
            }

            window.sessionStorage.setItem(
                artifactTabStorageKey,
                'music-prompt'
            );

            window.location.reload();
        } catch (error) {
            if (musicPromptFeedback) {
                musicPromptFeedback.textContent =
                    error instanceof Error
                        ? error.message
                        : (
                            'The Music Style Generation '
                            + 'Prompt could not be generated.'
                        );

                musicPromptFeedback.hidden = false;

                musicPromptFeedback.classList.add(
                    'forge-music-prompt__feedback--error'
                );
            }
        } finally {
            if (
                musicPromptGenerateButton
                && musicPromptGenerateButton.isConnected
            ) {
                musicPromptGenerateButton.disabled =
                    false;

                musicPromptGenerateButton.textContent =
                    originalButtonText;
            }
        }
    };

    if (musicPromptGenerateForm) {
        musicPromptGenerateForm.addEventListener(
            'submit',
            (event) => {
                event.preventDefault();

                void generateMusicPrompt();
            }
        );
    }

    /*
|--------------------------------------------------------------------------
| Copy Music Style Generation Prompt
|--------------------------------------------------------------------------
*/

const copyMusicPrompt = async () => {
    if (
        !musicPromptContent
        || !musicPromptCopyButton
    ) {
        return;
    }

    const promptText =
        musicPromptContent.textContent.trim();

    if (promptText === '') {
        return;
    }

    const originalButtonText =
        musicPromptCopyButton.textContent;

    musicPromptCopyButton.disabled = true;

    try {
        if (
            navigator.clipboard
            && window.isSecureContext
        ) {
            await navigator.clipboard.writeText(
                promptText
            );
        } else {
            const temporaryTextarea =
                document.createElement(
                    'textarea'
                );

            temporaryTextarea.value =
                promptText;

            temporaryTextarea.setAttribute(
                'readonly',
                ''
            );

            temporaryTextarea.style.position =
                'fixed';

            temporaryTextarea.style.opacity =
                '0';

            document.body.append(
                temporaryTextarea
            );

            temporaryTextarea.select();

            const copied =
                document.execCommand(
                    'copy'
                );

            temporaryTextarea.remove();

            if (!copied) {
                throw new Error(
                    'The prompt could not be copied.'
                );
            }
        }

        musicPromptCopyButton.textContent =
            'Copied';

        if (musicPromptFeedback) {
            musicPromptFeedback.hidden = false;

            musicPromptFeedback.textContent =
                'Music prompt copied. It is ready to paste into Suno.';

            musicPromptFeedback.classList.remove(
                'forge-music-prompt__feedback--error'
            );
        }

        window.setTimeout(
            () => {
                if (
                    musicPromptCopyButton
                    && musicPromptCopyButton.isConnected
                ) {
                    musicPromptCopyButton.textContent =
                        originalButtonText;
                }
            },
            1800
        );
    } catch (error) {
        musicPromptCopyButton.textContent =
            originalButtonText;

        if (musicPromptFeedback) {
            musicPromptFeedback.hidden = false;

            musicPromptFeedback.textContent =
                error instanceof Error
                    ? error.message
                    : (
                        'The prompt could not '
                        + 'be copied.'
                    );

            musicPromptFeedback.classList.add(
                'forge-music-prompt__feedback--error'
            );
        }
    } finally {
        if (
            musicPromptCopyButton
            && musicPromptCopyButton.isConnected
        ) {
            musicPromptCopyButton.disabled =
                false;
        }
    }
};

if (musicPromptCopyButton) {
    musicPromptCopyButton.addEventListener(
        'click',
        () => {
            void copyMusicPrompt();
        }
    );
}

    /*
    |--------------------------------------------------------------------------
    | Lyrics generation
    |--------------------------------------------------------------------------
    */

    const generateLyrics = async () => {
        if (
            !lyricsGenerateForm
            || !lyricsGenerateButton
        ) {
            return;
        }

        const originalButtonText =
            lyricsGenerateButton.textContent;

        lyricsGenerateButton.disabled = true;

        lyricsGenerateButton.textContent =
            'Forging Lyrics...';

        if (lyricsFeedback) {
            lyricsFeedback.hidden = false;

            lyricsFeedback.textContent =
                'Transforming the confirmed creative foundation into finished lyrics...';

            lyricsFeedback.classList.remove(
                'forge-lyrics__feedback--error'
            );
        }

        try {
            const response = await fetch(
                lyricsGenerateForm.action,
                {
                    method: 'POST',

                    body: new FormData(
                        lyricsGenerateForm
                    ),

                    headers: {
                        'Accept':
                            'application/json',

                        'X-Requested-With':
                            'XMLHttpRequest'
                    },

                    credentials:
                        'same-origin',

                    cache:
                        'no-store'
                }
            );

            const payload =
                await readJsonResponse(
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
                || !payload.data?.artifact
            ) {
                throw new Error(
                    payload.error?.message
                    ?? 'The lyrics could not be generated.'
                );
            }

            window.sessionStorage.setItem(
                artifactTabStorageKey,
                'lyrics'
            );

            window.location.reload();
        } catch (error) {
            if (lyricsFeedback) {
                lyricsFeedback.textContent =
                    error instanceof Error
                        ? error.message
                        : 'The lyrics could not be generated.';

                lyricsFeedback.hidden = false;

                lyricsFeedback.classList.add(
                    'forge-lyrics__feedback--error'
                );
            }
        } finally {
            if (
                lyricsGenerateButton
                && lyricsGenerateButton.isConnected
            ) {
                lyricsGenerateButton.disabled = false;

                lyricsGenerateButton.textContent =
                    originalButtonText;
            }
        }
    };

    if (lyricsGenerateForm) {
        lyricsGenerateForm.addEventListener(
            'submit',
            (event) => {
                event.preventDefault();

                void generateLyrics();
            }
        );
    }


})();
