<?php

namespace yii2tech\tests\unit\config;

use Yii;
use yii2tech\config\Item;
use yii2tech\config\Manager;
use yii2tech\config\StoragePhp;

/**
 * Test case for the extension [[Manager]].
 * @see Manager
 */
class ManagerTest extends TestCase
{
    public function tearDown()
    {
        $fileName = $this->getTestFileName();
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        parent::tearDown();
    }

    /**
     * @return string test file name
     */
    protected function getTestFileName()
    {
        return Yii::getAlias('@yii2tech/tests/unit/config/runtime') . DIRECTORY_SEPARATOR . 'test_config_' . getmypid() . '.php';
    }

    /**
     * Creates test config manager.
     * @return Manager config manager instance.
     */
    protected function createTestManager()
    {
        return new Manager([
            'storage' => [
                'class' => StoragePhp::className(),
                'fileName' => $this->getTestFileName(),
            ],
        ]);
    }

    // Tests :

    public function testSetGet()
    {
        $manager = new Manager();

        $items = [
            new Item(),
            new Item(),
        ];
        $manager->setItems($items);
        $this->assertEquals($items, $manager->getItems(), 'Unable to setup items!');

        $storage = new StoragePhp();
        $manager->setStorage($storage);
        $this->assertEquals($storage, $manager->getStorage(), 'Unable to setup storage!');
    }

    /**
     * @depends testSetGet
     */
    public function testGetDefaultStorage()
    {
        $manager = new Manager();
        $storage = $manager->getStorage();
        $this->assertTrue(is_object($storage), 'Unable to get default storage!');
    }

    /**
     * @depends testSetGet
     */
    public function testGetItemById()
    {
        $manager = new Manager();

        $itemId = 'testItemId';
        $item = new Item();
        $manager->setItems([
            $itemId => $item
        ]);
        $this->assertEquals($item, $manager->getItem($itemId), 'Unable to get item by id!');
    }

    /**
     * @depends testGetItemById
     */
    public function testCreateItem()
    {
        $manager = new Manager();

        $itemId = 'testItemId';
        $itemConfig = [
            'label' => 'testLabel'
        ];
        $manager->setItems([
            $itemId => $itemConfig
        ]);
        $item = $manager->getItem($itemId);
        $this->assertTrue(is_object($item), 'Unable to create item from config!');
        $this->assertEquals($itemConfig['label'], $item->label, 'Unable to setup attributes!');
    }

    /**
     * @depends testCreateItem
     */
    public function testSetupItemsByFile()
    {
        $manager = new Manager();

        $items = [
            'item1' => [
                'label' => 'item1label'
            ],
            'item2' => [
                'label' => 'item2label'
            ],
        ];
        $fileName = $this->getTestFileName();
        $fileContent = '<?php return ' . var_export($items, true) . ';';
        file_put_contents($fileName, $fileContent);

        $manager->setItems($fileName);

        foreach ($items as $id => $itemConfig) {
            $item = $manager->getItem($id);
            $this->assertEquals($itemConfig['label'], $item->label, 'Wrong item label');
        }
    }

    /**
     * @depends testCreateItem
     */
    public function testSetupItemValues()
    {
        $manager = new Manager();
        $items = [
            'item1' => [],
            'item2' => [],
        ];
        $manager->setItems($items);

        $itemValues = [
            'item1' => 'item1value',
            'item2' => 'item2value',
        ];
        $manager->setItemValues($itemValues);
        $this->assertEquals($itemValues, $manager->getItemValues(), 'Unable to setup item values!');
    }

    /**
     * @depends testCreateItem
     */
    public function testComposeConfig()
    {
        $manager = new Manager();
        $items = [
            'item1' => [
                'path' => 'params.item1',
                'value' => 'item1value',
            ],
            'item2' => [
                'path' => 'params.item2',
                'value' => 'item2value',
            ],
        ];
        $manager->setItems($items);

        $config = $manager->composeConfig();
        $expectedConfig = [
            'params' => [
                'item1' => 'item1value',
                'item2' => 'item2value',
            ],
        ];
        $this->assertEquals($expectedConfig, $config, 'Wrong config composed!');
    }

    /**
     * @depends testSetupItemValues
     */
    public function testStoreValues()
    {
        $manager = $this->createTestManager();
        $items = [
            'item1' => [
                'value' => 'item1value',
            ],
            'item2' => [
                'value' => 'item2value',
            ],
        ];
        $manager->setItems($items);

        $this->assertTrue($manager->saveValues(), 'Unable to save values!');
        $itemValues = $manager->getItemValues();

        $emptyItemValues = [
            'item1' => null,
            'item2' => null,
        ];

        $manager->setItemValues($emptyItemValues);
        $manager->restoreValues();
        $this->assertEquals($itemValues, $manager->getItemValues(), 'Unable to restore values!');

        $manager->clearValues();

        $manager->setItemValues($emptyItemValues);
        $this->assertEquals($emptyItemValues, $manager->getItemValues(), 'Unable to clear values!');
    }

    /**
     * @depends testComposeConfig
     * @depends testStoreValues
     */
    public function testFetchConfig()
    {
        $manager = $this->createTestManager();
        $items = [
            'item1' => [
                'path' => 'params.item1',
                'value' => 'item1value',
            ],
            'item2' => [
                'path' => 'params.item2',
                'value' => 'item2value',
            ],
        ];
        $manager->setItems($items);
        $manager->saveValues();

        $manager = $this->createTestManager();
        $manager->setItems($items);

        $config = $manager->fetchConfig();
        $expectedConfig = [
            'params' => [
                'item1' => 'item1value',
                'item2' => 'item2value',
            ],
        ];
        $this->assertEquals($expectedConfig, $config, 'Wrong config composed!');
    }

    /**
     * @depends testSetupItemValues
     */
    public function testValidate()
    {
        $manager = new Manager();

        $itemId = 'testItem';
        $items = [
            $itemId => [
                'rules' => [
                    ['required']
                ]
            ],
        ];
        $manager->setItems($items);

        $itemValues = [
            $itemId => ''
        ];
        $manager->setItemValues($itemValues);
        $this->assertFalse($manager->validate(), 'Invalid values considered as valid!');

        $itemValues = [
            $itemId => 'some value'
        ];
        $manager->setItemValues($itemValues);
        $this->assertTrue($manager->validate(), 'Valid values considered as invalid!');
    }
}