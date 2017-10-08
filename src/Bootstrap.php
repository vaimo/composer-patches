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

    public function apply($devMode = false)
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();

        $this->appliedPatchesManager->restoreAppliedPatchesInfo($repository);

        $repositoryManager = $this->repositoryManagerFactory->create($devMode);
        
        $repositoryManager->processRepository($repository);
    }
}
