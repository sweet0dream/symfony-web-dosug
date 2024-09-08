<?php

namespace App\AppBundle\Twig;

use DateTimeImmutable;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public const array MONTH = [
        '01' => 'января',
        '02' => 'февраля',
        '03' => 'марта',
        '04' => 'апреля',
        '05' => 'мая',
        '06' => 'июня',
        '07' => 'июля',
        '08' => 'августа',
        '09' => 'сентября',
        '10' => 'октября',
        '11' => 'ноября',
        '12' => 'декабря'
    ];
    public function getFilters(): array
    {
        return array(
            new TwigFilter('format_date', array($this, 'formatDate')),
            new TwigFilter('format_event', array($this, 'formatEvent')),
        );
    }

    public function formatDate(DateTimeImmutable $datetime): string
    {
        $now = new DateTimeImmutable('now');
        $year = $datetime->format('Y') != $now->format('Y')
            ? ' ' . $datetime->format('Y') . ' года'
            : '';
        return match ($datetime->diff($now)->format('%d')) {
            '0' => 'сегодня',
            '1' => 'вчера',
            '2' => 'позавчера',
            default => $datetime->format('d') . ' ' . self::MONTH[$datetime->format('m')] . $year,
        } . ' в ' . $datetime->format('H:i');
    }

    public function formatEvent(string $event): Markup
    {
        $event = unserialize($event);
        $keyAction = key($event);
        $valueAction = $event[$keyAction];

        $changeStatus = [
            'active' => ['скрыта', 'опубликована'],
            'premium' => ['снят статус премиум', 'назначен статус премиум'],
            'realy' => ['удален статус реальной', 'установлен статус реальной'],
            'top' => ['', 'была поднята']
        ];

        $changePriority = [
            'on' => 'установлен приоритет на ' . $valueAction['value'] . ' место',
            'off' => 'снят приоритет'
        ];

        $changePhotos = [
            'added' => 'добавлено фото ' . $valueAction['value'],
            'removed' => 'удалено ' . $valueAction['value'] . ' фото',
            'has_main' => $valueAction['value'] . ' установлено главным фото<a href="#">!!!</a>'
        ];

        return new Markup(match($keyAction) {
            'change_status' => $changeStatus[$valueAction['action']][$valueAction['value']],
            'change_priority' => $changePriority[$valueAction['action']],
            'change_photos' => $changePhotos[$valueAction['action']]
        }, 'UTF-8');
    }
}