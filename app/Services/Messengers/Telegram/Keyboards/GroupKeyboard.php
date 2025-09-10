<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Keyboards;

use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;

class GroupKeyboard
{

    public static function selectGroup(string $username, string $text = 'Selecione um GRUPO'): Keyboard
    {
        return Keyboard::make()
            ->inline()
            ->setSelective(true)
            ->row([
                Button::make()
                    ->setText($text)
                    ->setUrl(sprintf('https://t.me/%s?startgroup=start', $username))
            ]);
    }

}
