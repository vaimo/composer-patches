<?php
namespace Vaimo\ComposerPatches\Tests;

use Vaimo\ComposerPatches\Patch\DefinitionsProcessor as Model;

class DefinitionsProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testFlattenShouldReturnFlatVersionOfPatchDefinitionPerPackage()
    {
        $model = new Model();

        $patches = array(
            'some/package' => array(
                array(
                    'source' => '/some/path/fix.patch',
                    'label' => 'My patch label'
                ),
                array(
                    'source' => 'second/path/fix.patch',
                    'label' => 'My second patch'
                )
            ),
            'other/package' => array(
                array(
                    'source' => '/other/path/fix.patch',
                    'label' => 'Other patch'
                )
            )
        );

        $result = $model->flatten($patches);

        $this->assertEquals(array(
            'some/package' => array(
                '/some/path/fix.patch' => 'My patch label',
                'second/path/fix.patch' => 'My second patch'
            ),
            'other/package' => array(
                '/other/path/fix.patch' => 'Other patch'
            ),
        ), $result);
    }

    public function testFlattenShouldAppendCheckSumToDescriptionWhenDefined()
    {
        $model = new Model();

        $patches = array(
            'some/package' => array(
                array(
                    'source' => '/some/path/fix.patch',
                    'label' => 'My patch label',
                    'md5' => '1234'
                ),
                array(
                    'source' => '/some/path/other.patch',
                    'label' => 'My other label',
                    'md5' => '4567'
                )
            )
        );

        $result = $model->flatten($patches);

        $this->assertEquals(array(
            'some/package' => array(
                '/some/path/fix.patch' => 'My patch label, md5:1234',
                '/some/path/other.patch' => 'My other label, md5:4567',
            )
        ), $result);
    }
}
