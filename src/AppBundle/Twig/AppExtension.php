<?php

namespace App\AppBundle\Twig;

use DateTimeImmutable;
use Twig\Extension\AbstractExtension;
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
}