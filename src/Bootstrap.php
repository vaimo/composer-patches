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
    }

    public function prepare()
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();
        
        $this->appliedPatchesManager->extractAppliedPatchesInfo($repository);
    }

    public function apply($devMode = false, array $targets = array())
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();
        $configData = $this->composer->getPackage()->getExtra();

        $this->appliedPatchesManager->restoreAppliedPatchesInfo($repository);
        
        if (!$repositoryManager = $this->repositoryManagerFactory->create($devMode, $configData)) {
            return null;
        }
        
        $repositoryManager->processRepository(
            $repository, 
            array_fill_keys($targets, true)
        );
    }
    
    public function unload(array $targets = array())
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();

        $repositoryManager = $this->repositoryManagerFactory->create(
            false,
            $targets 
                ? $this->composer->getPackage()->getExtra() 
                : array(\Vaimo\ComposerPatches\Patch\Config::ENABLED => false)
        );

        $repositoryManager->processRepository(
            $repository, 
            array_fill_keys($targets, false)
        );
    }
}
