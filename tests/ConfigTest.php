<?php
use DD\DiMaria as DiMaria;

class ConfigTest extends AbstractTest
{
    public function testSetConfigCanSetConfigForMultipleClasses()
    {
        $this->di->setRules([
            'aliases' => [
                'LargeTV' => [TV::class],
                'SmallTV' => [
                    TV::class, [
                        'inches' => 12
                    ]
                ]
            ],
            'params' => [
                TV::class => [
                    'inches' => 55
                ]
            ],
            'shared' => [
                'LargeTV' => true
            ]
        ]);

        $tv = $this->di->get(TV::class);
        $tvLarge1 = $this->di->get('LargeTV');
        $tvLarge2 = $this->di->get('LargeTV');
        $tvSmall1 = $this->di->get('SmallTV');
        $tvSmall2 = $this->di->get('SmallTV');

        $tvLarge2->inches = 100;
        $tvSmall2->inches = 1;

        $this->assertEquals(55, $tv->inches);
        $this->assertEquals(TV::class, get_class($tvLarge1));
        $this->assertEquals(12, $tvSmall1->inches);

        $this->assertEquals($tvLarge1->inches, $tvLarge2->inches);
        $this->assertNotEquals($tvSmall1->inches, $tvSmall2->inches);
    }

    public function testSetterInjectionCanBeSetInConfig()
    {
        $loggers = ['foo', 'bar'];

        $this->di->setRules([
            'injections' => [
                'SetterInjectionTestClasses\Logger' => [
                    [
                        'addLogger', [
                            'logger' => $loggers[0]
                        ]
                    ],
                    [
                        'addLogger', [
                            'logger' => $loggers[1]
                        ]
                    ],
                ]
            ]
        ]);

        $logger = $this->di->get('SetterInjectionTestClasses\Logger');
        $this->assertEquals($loggers, $logger->loggers);
    }
}
