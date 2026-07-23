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
use SonicFoundry\Progress\ProgressPresenter;
use SonicFoundry\Workflow\PillarWorkflowRepository;
use SonicFoundry\Workflow\PillarWorkflowService;
use SonicFoundry\Workflow\WorkflowPresenter;
use SonicFoundry\Emotion\EmotionMemoryExtractor;
use SonicFoundry\Pillars\Definitions\EmotionDefinition;
use SonicFoundry\Pillars\Definitions\StoryDefinition;
use SonicFoundry\Pillars\Registry\PillarRegistry;
use SonicFoundry\Progress\PillarProgressEvaluator;
use SonicFoundry\Identity\IdentityMemoryExtractor;
use SonicFoundry\Pillars\Definitions\IdentityDefinition;
use SonicFoundry\Sound\SoundMemoryExtractor;
use SonicFoundry\Pillars\Definitions\SoundDefinition;
use SonicFoundry\Impact\ImpactMemoryExtractor;
use SonicFoundry\Pillars\Definitions\ImpactDefinition;
use SonicFoundry\Artifact\CreativeArtifactRepository;
use SonicFoundry\Artifact\CreativeArtifactService;

use SonicFoundry\Style\StyleGuideGenerator;
use SonicFoundry\Style\StyleGuideService;

use SonicFoundry\Lyrics\LyricsGenerator;
use SonicFoundry\Lyrics\LyricsService;

use SonicFoundry\Style\SongStyleGenerator;
use SonicFoundry\Style\SongStyleService;

use SonicFoundry\Music\MusicStyleGenerationPromptGenerator;
use SonicFoundry\Music\MusicStyleGenerationPromptService;


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

    private ?ProgressPresenter $progressPresenter = null;

    private ?PillarWorkflowRepository $pillarWorkflowRepository = null;

    private ?PillarWorkflowService $pillarWorkflowService = null;

    private ?WorkflowPresenter $workflowPresenter = null;

    private ?EmotionMemoryExtractor $emotionMemoryExtractor = null;

    private ?PillarRegistry $pillarRegistry = null;

    private ?PillarProgressEvaluator $pillarProgressEvaluator = null;

    private ?IdentityMemoryExtractor $identityMemoryExtractor = null;

    private ?SoundMemoryExtractor $soundMemoryExtractor = null;

    private ?ImpactMemoryExtractor $impactMemoryExtractor = null;

    private ?CreativeArtifactRepository $creativeArtifactRepository = null;

    private ?CreativeArtifactService $creativeArtifactService = null;

    private ?StyleGuideGenerator $styleGuideGenerator = null;

    private ?StyleGuideService $styleGuideService = null;

    private ?LyricsGenerator $lyricsGenerator = null;

    private ?LyricsService $lyricsService = null;

    private ?SongStyleGenerator $songStyleGenerator = null;

    private ?SongStyleService $songStyleService = null;

    private ?MusicStyleGenerationPromptGenerator $musicStyleGenerationPromptGenerator = null;

    private ?MusicStyleGenerationPromptService $musicStyleGenerationPromptService = null;
    

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
                    messages: $this->conversations(),
                    works: $this->workService(),
                    workflow: $this->workflowService(),
                );
          }

        return $this->conversationService;
    }

    /*
    |--------------------------------------------------------------------------
    | Pillar Definitions
    |--------------------------------------------------------------------------
    */

    public function pillarRegistry(): PillarRegistry
    {
        if (
            !$this->pillarRegistry
            instanceof PillarRegistry
        ) {
            $registry =
                new PillarRegistry();

            $registry->register(
                new StoryDefinition()
            );

            $registry->register(
                new EmotionDefinition()
            );

            $registry->register(
                new IdentityDefinition()
            );

            $registry->register(
                new SoundDefinition()
            );

            $registry->register(
                new ImpactDefinition()
            );

            $this->pillarRegistry =
                $registry;
        }

        return $this->pillarRegistry;
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
                new MemoryPresenter(
                    $this->pillarRegistry()
                );
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
                        
                    workflow: $this->workflowService()
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

    public function pillarProgressEvaluator(): PillarProgressEvaluator
    {
        if (
            !$this->pillarProgressEvaluator
            instanceof PillarProgressEvaluator
        ) {
            $this->pillarProgressEvaluator =
                new PillarProgressEvaluator(
                    openAI:
                        $this->openAI(),

                    prompts:
                        $this->prompts(),

                    memory:
                        $this->memoryService(),

                    works:
                        $this->workService(),

                    pillars:
                        $this->pillarRegistry(),
                );
        }

        return $this->pillarProgressEvaluator;
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

    

    public function emotionMemoryExtractor(): EmotionMemoryExtractor
    {
        if (
            !$this->emotionMemoryExtractor
            instanceof EmotionMemoryExtractor
        ) {
            $this->emotionMemoryExtractor =
                new EmotionMemoryExtractor(
                    openAI: $this->openAI(),
                    prompts: $this->prompts(),
                    messages: $this->conversations(),
                    memory: $this->memoryService(),
                    works: $this->workService(),
                );
        }

        return $this->emotionMemoryExtractor;
    }

    public function identityMemoryExtractor(): IdentityMemoryExtractor
    {
        if (
            !$this->identityMemoryExtractor
            instanceof IdentityMemoryExtractor
        ) {
            $this->identityMemoryExtractor =
                new IdentityMemoryExtractor(
                    openAI: $this->openAI(),
                    prompts: $this->prompts(),
                    messages: $this->conversations(),
                    memory: $this->memoryService(),
                    works: $this->workService(),
                );
        }

        return $this->identityMemoryExtractor;
    }

    public function soundMemoryExtractor(): SoundMemoryExtractor
    {
        if (
            !$this->soundMemoryExtractor
            instanceof SoundMemoryExtractor
        ) {
            $this->soundMemoryExtractor =
                new SoundMemoryExtractor(
                    openAI: $this->openAI(),
                    prompts: $this->prompts(),
                    messages: $this->conversations(),
                    memory: $this->memoryService(),
                    works: $this->workService(),
                );
        }

        return $this->soundMemoryExtractor;
    }

    public function impactMemoryExtractor(): ImpactMemoryExtractor
    {
        if (
            !$this->impactMemoryExtractor
            instanceof ImpactMemoryExtractor
        ) {
            $this->impactMemoryExtractor =
                new ImpactMemoryExtractor(
                    openAI: $this->openAI(),
                    prompts: $this->prompts(),
                    messages: $this->conversations(),
                    memory: $this->memoryService(),
                    works: $this->workService(),
                );
        }

        return $this->impactMemoryExtractor;
    }

    /*
    |--------------------------------------------------------------------------
    | Creative Artifacts
    |--------------------------------------------------------------------------
    */

    public function artifacts(): CreativeArtifactRepository
    {
        if (
            !$this->creativeArtifactRepository
            instanceof CreativeArtifactRepository
        ) {
            $this->creativeArtifactRepository =
                new CreativeArtifactRepository(
                    $this->database()
                );
        }

        return $this->creativeArtifactRepository;
    }

    public function artifactService(): CreativeArtifactService
    {
        if (
            !$this->creativeArtifactService
            instanceof CreativeArtifactService
        ) {
            $this->creativeArtifactService =
                new CreativeArtifactService(
                    artifacts:
                        $this->artifacts(),

                    works:
                        $this->workService(),
                );
        }

        return $this->creativeArtifactService;
    }

    public function styleGuideGenerator(): StyleGuideGenerator
    {
        if (
            !$this->styleGuideGenerator
            instanceof StyleGuideGenerator
        ) {
            $this->styleGuideGenerator =
                new StyleGuideGenerator(
                    openAI:
                        $this->openAI(),

                    prompts:
                        $this->prompts(),

                    memory:
                        $this->memoryService(),

                    works:
                        $this->workService(),
                );
        }

        return $this->styleGuideGenerator;
    }

    public function styleGuideService(): StyleGuideService
    {
        if (
            !$this->styleGuideService
            instanceof StyleGuideService
        ) {
            $this->styleGuideService =
                new StyleGuideService(
                    generator:
                        $this->styleGuideGenerator(),

                    artifacts:
                        $this->artifactService(),
                );
        }

        return $this->styleGuideService;
    }

    public function lyricsGenerator(): LyricsGenerator
    {
        if (
            !$this->lyricsGenerator
            instanceof LyricsGenerator
        ) {
            $this->lyricsGenerator =
                new LyricsGenerator(
                    openAI:
                        $this->openAI(),

                    prompts:
                        $this->prompts(),

                    memory:
                        $this->memoryService(),

                    artifacts:
                        $this->artifactService(),

                    works:
                        $this->workService(),
                );
        }

        return $this->lyricsGenerator;
    }

    public function lyricsService(): LyricsService
    {
        if (
            !$this->lyricsService
            instanceof LyricsService
        ) {
            $this->lyricsService =
                new LyricsService(
                    generator:
                        $this->lyricsGenerator(),

                    artifacts:
                        $this->artifactService(),
                );
        }

        return $this->lyricsService;
    }

    public function songStyleGenerator(): SongStyleGenerator
    {
        if (
            !$this->songStyleGenerator
            instanceof SongStyleGenerator
        ) {
            $this->songStyleGenerator =
                new SongStyleGenerator(
                    openAI:
                        $this->openAI(),

                    prompts:
                        $this->prompts(),

                    memory:
                        $this->memoryService(),

                    artifacts:
                        $this->artifactService(),

                    works:
                        $this->workService(),
                );
        }

        return $this->songStyleGenerator;
    }

    public function songStyleService(): SongStyleService
    {
        if (
            !$this->songStyleService
            instanceof SongStyleService
        ) {
            $this->songStyleService =
                new SongStyleService(
                    generator:
                        $this->songStyleGenerator(),

                    artifacts:
                        $this->artifactService(),
                );
        }

        return $this->songStyleService;
    }

    public function musicStyleGenerationPromptGenerator():
        MusicStyleGenerationPromptGenerator
    {
        if (
            !$this->musicStyleGenerationPromptGenerator
            instanceof MusicStyleGenerationPromptGenerator
        ) {
            $this->musicStyleGenerationPromptGenerator =
                new MusicStyleGenerationPromptGenerator(
                    openAI:
                        $this->openAI(),

                    prompts:
                        $this->prompts(),

                    artifacts:
                        $this->artifactService(),

                    works:
                        $this->workService(),
                );
        }

        return $this
            ->musicStyleGenerationPromptGenerator;
    }

    public function musicStyleGenerationPromptService():
        MusicStyleGenerationPromptService
    {
        if (
            !$this->musicStyleGenerationPromptService
            instanceof MusicStyleGenerationPromptService
        ) {
            $this->musicStyleGenerationPromptService =
                new MusicStyleGenerationPromptService(
                    generator:
                        $this
                            ->musicStyleGenerationPromptGenerator(),

                    artifacts:
                        $this->artifactService(),
                );
        }

        return $this
            ->musicStyleGenerationPromptService;
    }
}