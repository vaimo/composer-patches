<?php
namespace Vaimo\ComposerPatches;

class Bootstrap
{
    /**
     * @var \Composer\Composer
     */
    private $composer;
    
    /**
     * @var \Vaimo\ComposerPatches\Managers\AppliedPatchesManager
     */
    private $appliedPatchesManager;
    
    /**
     * @var \Vaimo\ComposerPatches\Factories\RepositoryManagerFactory
     */
    private $repositoryManagerFactory;

    /**
     * @var \Vaimo\ComposerPatches\Managers\RepositoryStateManager
     */
    private $repositoryStateManager;
    
    /**
     * @param \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(
        \Composer\Composer $composer, 
        \Composer\IO\IOInterface $io
    ) {
        $this->composer = $composer;
        
        $this->appliedPatchesManager = new \Vaimo\ComposerPatches\Managers\AppliedPatchesManager();
        
        $this->repositoryManagerFactory = new \Vaimo\ComposerPatches\Factories\RepositoryManagerFactory(
            $composer,
            $io
        );

        $this->repositoryStateManager = new \Vaimo\ComposerPatches\Managers\RepositoryStateManager();
    }

    public function prepare()
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();

        $this->repositoryStateManager->extractAppliedPatchesInfo($repository);
    }

    public function apply($devMode = false, array $targets = array(), $filters = array())
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();
        $configData = $this->composer->getPackage()->getExtra();

        $this->repositoryStateManager->restoreAppliedPatchesInfo($repository);
        
        if (!$repositoryManager = $this->repositoryManagerFactory->create($devMode, $configData)) {
            return null;
        }
        
        $repositoryManager->processRepository(
            $repository, 
            array_fill_keys($targets, true),
            $filters
        );
    }
    
    public function unload(array $targets = array())
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();

        $rootConfig = $this->composer->getPackage()->getExtra();
        $config = $targets ? $rootConfig : array(\Vaimo\ComposerPatches\Patch\Config::ENABLED => false);
        
        $repositoryManager = $this->repositoryManagerFactory->create(false, $config);

        $repositoryManager->processRepository(
            $repository, 
            array_fill_keys($targets, false)
        );
    }
}
