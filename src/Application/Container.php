<?php
declare(strict_types=1);

namespace SonicFoundry\Application;

use Google\Client as GoogleClient;
use PDO;
use SonicFoundry\AI\OpenAIClient;
use SonicFoundry\AI\PromptAssembler;
use SonicFoundry\AI\PromptLoader;
use SonicFoundry\Auth\Auth;
use SonicFoundry\Auth\GoogleAuthenticator;
use SonicFoundry\Auth\GoogleLoginService;
use SonicFoundry\Auth\PasswordLoginService;
use SonicFoundry\Auth\RegistrationService;
use SonicFoundry\Conversation\ConversationRepository;
use SonicFoundry\Conversation\ConversationService;
use SonicFoundry\Database\Connection;
use SonicFoundry\Forge\CreativePartnerService;
use SonicFoundry\Memory\MemoryPresenter;
use SonicFoundry\Memory\PillarMemoryRepository;
use SonicFoundry\Memory\PillarMemoryService;
use SonicFoundry\Story\StoryMemoryExtractor;
use SonicFoundry\User\UserRepository;
use SonicFoundry\Work\WorkRepository;
use SonicFoundry\Work\WorkService;

use SonicFoundry\Progress\PillarProgressRepository;
use SonicFoundry\Progress\PillarProgressService;
use SonicFoundry\Story\StoryProgressEvaluator;

use SonicFoundry\Progress\ProgressPresenter;

use SonicFoundry\Workflow\PillarWorkflowRepository;
use SonicFoundry\Workflow\PillarWorkflowService;
use SonicFoundry\Workflow\WorkflowPresenter;

final class Container
{
    private ?PDO $database = null;

    private ?UserRepository $userRepository = null;

    private ?Auth $auth = null;

    private ?RegistrationService $registrationService = null;

    private ?PasswordLoginService $passwordLoginService = null;

    private ?GoogleAuthenticator $googleAuthenticator = null;

    private ?GoogleLoginService $googleLoginService = null;

    private ?WorkRepository $workRepository = null;

    private ?WorkService $workService = null;

    private ?ConversationRepository $conversationRepository = null;

    private ?ConversationService $conversationService = null;

    private ?PillarMemoryRepository $pillarMemoryRepository = null;

    private ?PillarMemoryService $pillarMemoryService = null;

    private ?MemoryPresenter $memoryPresenter = null;

    private ?PromptLoader $promptLoader = null;

    private ?PromptAssembler $promptAssembler = null;

    private ?OpenAIClient $openAIClient = null;

    private ?CreativePartnerService $creativePartnerService = null;

    private ?StoryMemoryExtractor $storyMemoryExtractor = null;

    private ?PillarProgressRepository $pillarProgressRepository = null;

    private ?PillarProgressService $pillarProgressService = null;

    private ?StoryProgressEvaluator $storyProgressEvaluator = null;

    private ?ProgressPresenter $progressPresenter = null;

    private ?PillarWorkflowRepository $pillarWorkflowRepository = null;

    private ?PillarWorkflowService $pillarWorkflowService = null;

    private ?WorkflowPresenter $workflowPresenter = null;

    

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */

    public function database(): PDO
    {
        if (!$this->database instanceof PDO) {
            $this->database = Connection::get();
        }

        return $this->database;
    }

    /*
    |--------------------------------------------------------------------------
    | Users and Authentication
    |--------------------------------------------------------------------------
    */

    public function users(): UserRepository
    {
        if (
            !$this->userRepository
            instanceof UserRepository
        ) {
            $this->userRepository =
                new UserRepository(
                    $this->database()
                );
        }

        return $this->userRepository;
    }

    public function auth(): Auth
    {
        if (!$this->auth instanceof Auth) {
            $this->auth = new Auth(
                $this->users()
            );
        }

        return $this->auth;
    }

    public function registration(): RegistrationService
    {
        if (
            !$this->registrationService
            instanceof RegistrationService
        ) {
            $this->registrationService =
                new RegistrationService(
                    users: $this->users(),
                    auth: $this->auth(),
                );
        }

        return $this->registrationService;
    }

    public function passwordLogin(): PasswordLoginService
    {
        if (
            !$this->passwordLoginService
            instanceof PasswordLoginService
        ) {
            $this->passwordLoginService =
                new PasswordLoginService(
                    users: $this->users(),
                    auth: $this->auth(),
                );
        }

        return $this->passwordLoginService;
    }

    /*
    |--------------------------------------------------------------------------
    | Google Authentication
    |--------------------------------------------------------------------------
    */

    public function googleAuthenticator(): GoogleAuthenticator
    {
        if (
            !$this->googleAuthenticator
            instanceof GoogleAuthenticator
        ) {
            $clientId = \env(
                'GOOGLE_CLIENT_ID'
            );

            if (
                !is_string($clientId)
                || trim($clientId) === ''
            ) {
                throw new \RuntimeException(
                    'GOOGLE_CLIENT_ID is not configured.'
                );
            }

            $googleClient = new GoogleClient([
                'client_id' => $clientId,
            ]);

            $this->googleAuthenticator =
                new GoogleAuthenticator(
                    $googleClient
                );
        }

        return $this->googleAuthenticator;
    }

    public function googleLogin(): GoogleLoginService
    {
        if (
            !$this->googleLoginService
            instanceof GoogleLoginService
        ) {
            $this->googleLoginService =
                new GoogleLoginService(
                    googleAuthenticator:
                        $this->googleAuthenticator(),

                    users:
                        $this->users(),

                    auth:
                        $this->auth(),
                );
        }

        return $this->googleLoginService;
    }

    /*
    |--------------------------------------------------------------------------
    | Works
    |--------------------------------------------------------------------------
    */

    public function works(): WorkRepository
    {
        if (
            !$this->workRepository
            instanceof WorkRepository
        ) {
            $this->workRepository =
                new WorkRepository(
                    $this->database()
                );
        }

        return $this->workRepository;
    }

    public function workService(): WorkService
    {
        if (
            !$this->workService
            instanceof WorkService
        ) {
            $this->workService =
                new WorkService(
                    $this->works()
                );
        }

        return $this->workService;
    }

    /*
    |--------------------------------------------------------------------------
    | Conversations
    |--------------------------------------------------------------------------
    */

    public function conversations(): ConversationRepository
    {
        if (
            !$this->conversationRepository
            instanceof ConversationRepository
        ) {
            $this->conversationRepository =
                new ConversationRepository(
                    $this->database()
                );
        }

        return $this->conversationRepository;
    }

    public function conversationService(): ConversationService
    {
        if (
            !$this->conversationService
            instanceof ConversationService
        ) {
            $this->conversationService =
                new ConversationService(
                    messages:
                        $this->conversations(),

                    works:
                        $this->workService(),
                );
        }

        return $this->conversationService;
    }

    /*
    |--------------------------------------------------------------------------
    | Memory Engine
    |--------------------------------------------------------------------------
    */

    public function memories(): PillarMemoryRepository
    {
        if (
            !$this->pillarMemoryRepository
            instanceof PillarMemoryRepository
        ) {
            $this->pillarMemoryRepository =
                new PillarMemoryRepository(
                    $this->database()
                );
        }

        return $this->pillarMemoryRepository;
    }

    public function memoryService(): PillarMemoryService
    {
        if (
            !$this->pillarMemoryService
            instanceof PillarMemoryService
        ) {
            $this->pillarMemoryService =
                new PillarMemoryService(
                    memories:
                        $this->memories(),

                    works:
                        $this->workService(),
                );
        }

        return $this->pillarMemoryService;
    }

    public function memoryPresenter(): MemoryPresenter
    {
        if (
            !$this->memoryPresenter
            instanceof MemoryPresenter
        ) {
            $this->memoryPresenter =
                new MemoryPresenter();
        }

        return $this->memoryPresenter;
    }

    /*
    |--------------------------------------------------------------------------
    | Prompt System
    |--------------------------------------------------------------------------
    */

    public function promptLoader(): PromptLoader
    {
        if (
            !$this->promptLoader
            instanceof PromptLoader
        ) {
            $this->promptLoader =
                new PromptLoader(
                    dirname(__DIR__, 2)
                        . '/prompts'
                );
        }

        return $this->promptLoader;
    }

    public function prompts(): PromptAssembler
    {
        if (
            !$this->promptAssembler
            instanceof PromptAssembler
        ) {
            $this->promptAssembler =
                new PromptAssembler(
                    $this->promptLoader()
                );
        }

        return $this->promptAssembler;
    }

    /*
    |--------------------------------------------------------------------------
    | OpenAI
    |--------------------------------------------------------------------------
    */

    public function openAI(): OpenAIClient
    {
        if (
            !$this->openAIClient
            instanceof OpenAIClient
        ) {
            $apiKey = \env(
                'OPENAI_API_KEY'
            );

            $model = \env(
                'OPENAI_MODEL'
            );

            if (
                !is_string($apiKey)
                || trim($apiKey) === ''
            ) {
                throw new \RuntimeException(
                    'OPENAI_API_KEY is not configured.'
                );
            }

            if (
                !is_string($model)
                || trim($model) === ''
            ) {
                throw new \RuntimeException(
                    'OPENAI_MODEL is not configured.'
                );
            }

            $this->openAIClient =
                new OpenAIClient(
                    apiKey: $apiKey,
                    model: $model,
                );
        }

        return $this->openAIClient;
    }

    /*
    |--------------------------------------------------------------------------
    | Creative Partner
    |--------------------------------------------------------------------------
    */

    public function creativePartner(): CreativePartnerService
    {
        if (
            !$this->creativePartnerService
            instanceof CreativePartnerService
        ) {
            $this->creativePartnerService =
                new CreativePartnerService(
                    openAI:
                        $this->openAI(),

                    prompts:
                        $this->prompts(),

                    messages:
                        $this->conversations(),

                    works:
                        $this->workService(),

                    memory:
                        $this->memoryService(),
                );
        }

        return $this->creativePartnerService;
    }

    /*
    |--------------------------------------------------------------------------
    | Progress Engine
    |--------------------------------------------------------------------------
    */

    public function progress(): PillarProgressRepository
    {
        if (
            !$this->pillarProgressRepository
            instanceof PillarProgressRepository
        ) {
            $this->pillarProgressRepository =
                new PillarProgressRepository(
                    $this->database()
                );
        }

        return $this->pillarProgressRepository;
    }

    public function progressService(): PillarProgressService
    {
        if (
            !$this->pillarProgressService
            instanceof PillarProgressService
        ) {
            $this->pillarProgressService =
                new PillarProgressService(
                    progress: $this->progress(),
                    works: $this->workService(),
                );
        }

        return $this->pillarProgressService;
    }

    public function progressPresenter(): ProgressPresenter
    {
        if (
            !$this->progressPresenter
            instanceof ProgressPresenter
        ) {
            $this->progressPresenter =
                new ProgressPresenter();
        }

        return $this->progressPresenter;
    }

    /*
    |--------------------------------------------------------------------------
    | Pillar Workflow
    |--------------------------------------------------------------------------
    */

    public function workflows(): PillarWorkflowRepository
    {
        if (
            !$this->pillarWorkflowRepository
            instanceof PillarWorkflowRepository
        ) {
            $this->pillarWorkflowRepository =
                new PillarWorkflowRepository(
                    $this->database()
                );
        }

        return $this->pillarWorkflowRepository;
    }

    public function workflowService(): PillarWorkflowService
    {
        if (
            !$this->pillarWorkflowService
            instanceof PillarWorkflowService
        ) {
            $this->pillarWorkflowService =
                new PillarWorkflowService(
                    workflows: $this->workflows(),
                    progress: $this->progressService(),
                    works: $this->workService(),
                );
        }

        return $this->pillarWorkflowService;
    }

    public function workflowPresenter(): WorkflowPresenter
    {
        if (
            !$this->workflowPresenter
            instanceof WorkflowPresenter
        ) {
            $this->workflowPresenter =
                new WorkflowPresenter();
        }

        return $this->workflowPresenter;
    }

    /*
    |--------------------------------------------------------------------------
    | Story Specialist
    |--------------------------------------------------------------------------
    */

    public function storyMemoryExtractor(): StoryMemoryExtractor
    {
        if (
            !$this->storyMemoryExtractor
            instanceof StoryMemoryExtractor
        ) {
            $this->storyMemoryExtractor =
                new StoryMemoryExtractor(
                    openAI:
                        $this->openAI(),

                    prompts:
                        $this->prompts(),

                    messages:
                        $this->conversations(),

                    works:
                        $this->workService(),
                );
        }

        return $this->storyMemoryExtractor;
    }

    public function storyProgressEvaluator(): StoryProgressEvaluator
    {
        if (
            !$this->storyProgressEvaluator
            instanceof StoryProgressEvaluator
        ) {
            $this->storyProgressEvaluator =
                new StoryProgressEvaluator(
                    openAI: $this->openAI(),
                    prompts: $this->prompts(),
                    memory: $this->memoryService(),
                    works: $this->workService(),
                );
        }

        return $this->storyProgressEvaluator;
    }
}