<?php
use DD\DiMaria as DiMaria;

class PreferencesTest extends AbstractTest
{
    public function testPreferencesAreRequiredBecauseAliasesAsPreferencesBreaksSetterInjection()
    {
        $this->di->setAlias(RoomInterface::class, Kitchen::class);

        $this->di->setInjection(Kitchen::class, 'setToaster', [
            'toaster' => false
        ]);

        $house = $this->di->get(House::class);
        $this->assertEquals(true, $house->kitchen->toaster);
    }

    public function testPreferencesWork()
    {
        $this->di->setPreference(RoomInterface::class, Kitchen::class);

        $house = $this->di->get(House::class);
        $this->assertEquals(Kitchen::class, get_class($house->kitchen));
    }

    public function testPreferencesDontBreakSetterInjection()
    {
        $this->di->setPreference(RoomInterface::class, Kitchen::class);

        $this->di->setInjection(Kitchen::class, 'setToaster', [
            'toaster' => false
        ]);

        $house = $this->di->get(House::class);
        $this->assertEquals(false, $house->kitchen->toaster);
    }

    public function testPreferences2DontBreakSetterInjection()
    {
        $this->di->setAlias('Kitch', Kitchen::class);
        $this->di->setPreference(RoomInterface::class, 'Kitch');

        $this->di->setInjection('Kitch', 'setToaster', [
            'toaster' => false
        ]);

        $house = $this->di->get(House::class, ['kitchen' => ['instanceOf' => 'Kitch']]);
        $this->assertEquals(false, $house->kitchen->toaster);
    }
}
