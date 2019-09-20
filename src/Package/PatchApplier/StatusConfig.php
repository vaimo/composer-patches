<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package\PatchApplier;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

class StatusConfig
{
    public function getLabels()
    {
        return array(
            Patch::STATUS_NEW => '<info>NEW</info>',
            Patch::STATUS_ERRORS => '<fg=red>%s</>',
            Patch::STATUS_CHANGED => '<fg=yellow>CHANGED</>',
            Patch::STATUS_MATCH => '<info>MATCH</info>',
            Patch::STATUS_AFFECTED => '<fg=cyan>AFFECTED</>',
            Patch::STATUS_APPLIED => '<fg=white;options=bold>APPLIED</>',
            Patch::STATUS_REMOVED => '<fg=red>REMOVED</>',
            Patch::STATUS_EXCLUDED => '<fg=black>EXCLUDED</>',
            Patch::STATUS_UNKNOWN => 'UNKNOWN'
        );
    }
}
