<?php
declare(strict_types=1);

namespace ExpressRequest\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use ExpressRequest\Controller\Component\ExpressParamsComponent;

/**
 * ExpressRequest\Controller\Component\ExpressParamsComponent Test Case
 */
class ExpressParamsComponentTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \ExpressRequest\Controller\Component\ExpressParamsComponent
     */
    protected $ExpressParams;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->ExpressParams = new ExpressParamsComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ExpressParams);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
