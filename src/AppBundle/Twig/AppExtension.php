<?php

namespace App\AppBundle\Twig;

use App\Entity\Event;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
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
            new TwigFilter('sort_events', array($this, 'sortableEvents')),
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

    public function sortableEvents(Collection $allEvents): array
    {
        $now = new DateTimeImmutable('now');
        $result = [];
        foreach ($allEvents as $oneEvent) {
            if ($oneEvent instanceof Event) {
                $diffDay = $oneEvent->getCreatedAt()->diff($now)->format('%a');
                if ($diffDay == 0) {
                    $result['today'][] = $oneEvent;
                } elseif ($diffDay > 0 && $diffDay < 2) {
                    $result['yesterday'][] = $oneEvent;
                } else {
                    $result['otherdays'][] = $oneEvent;
                }
            }
        }

        return $result;
    }

    public function formatEvent(string $event): Markup
    {
        $event = unserialize($event);
        $keyAction = key($event);
        $valueAction = $event[$keyAction];

        return new Markup(
            match($keyAction) {
                'change_status' => [
                    'active' => ['скрыта', 'опубликована'],
                    'premium' => ['снят статус премиум', 'назначен статус премиум'],
                    'realy' => ['удален статус реальной', 'установлен статус реальной'],
                    'top' => ['', 'была поднята']
                ][$valueAction['action']][$valueAction['value']],
                'change_priority' => [
                    'on' => 'установлен приоритет на ' . $valueAction['value'] . ' место',
                    'off' => 'снят приоритет'
                ][$valueAction['action']],
                'change_photos' => [
                    'added' => 'добавлено ' . $this->getModalPhoto($valueAction['id'], 'новое фото', $valueAction['value']),
                    'removed' => 'удалено ' . $valueAction['value'] . ' фото',
                    'has_main' => 'установлено ' . $this->getModalPhoto($valueAction['id'], 'главное фото', $valueAction['value'])
                ][$valueAction['action']]
            },
            'UTF-8');
    }

    private function getModalPhoto(
        int $id,
        string $anchor,
        ?string $file
    ): string
    {
        return '
            <a href="#' . $file . '" data-bs-toggle="modal" class="text-success">' . $anchor . '</a>
            <div class="modal fade" id="' . $file . '" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content position-relative">
                        <button type="button" class="btn-close position-absolute top-0 start-100 translate-middle" data-bs-dismiss="modal" aria-label="Close"></button>
                        <img src="/media/' . $id . '/src/' . $file . '" alt="' . $file . '">
                    </div>
                </div>
            </div>
        ';
    }
}