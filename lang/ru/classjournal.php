<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Russian strings for mod_classjournal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addlesson'] = 'Добавить занятие';
$string['aggregation'] = 'Расчет итоговой оценки';
$string['aggregation_help'] = 'Определяет, как итог считается в журнале, журнале оценок Moodle и API. Сумма складывает баллы занятий и ограничивает результат максимумом итогового элемента Moodle. Среднее считает средний процент по занятиям и переводит его в заданный максимум итогового элемента.';
$string['aggregationavg'] = 'Среднее';
$string['aggregationavgdescription'] = 'Итоговая оценка: Moodle считает средний процент по занятиям с выставленными оценками и переводит его в шкалу {$a}. Пустые оценки игнорируются.';
$string['aggregationavgzerodescription'] = 'Итоговая оценка: Moodle считает средний процент по всем занятиям и переводит его в шкалу {$a}. Пустые оценки считаются как 0.';
$string['aggregationsum'] = 'Сумма';
$string['aggregationsumdescription'] = 'Итоговая оценка: баллы занятий суммируются, пустые оценки игнорируются, результат ограничивается значением {$a} в журнале оценок Moodle.';
$string['aggregationsumzerodescription'] = 'Итоговая оценка: баллы занятий суммируются, пустые оценки считаются как 0, результат ограничивается значением {$a} в журнале оценок Moodle.';
$string['applyfilters'] = 'Применить';
$string['calendarevents'] = 'Показывать даты занятий в календаре';
$string['calendarevents_desc'] = 'По умолчанию для новых журналов: публиковать дату каждого занятия как событие календаря курса.';
$string['calendarevents_help'] = 'Если включено, дата каждого занятия публикуется как событие в календаре курса, а при изменении занятий события обновляются и удаляются. Отключите, если эти даты уже добавляет другой плагин (например, модуль посещаемости), чтобы не было дублей в календаре.';
$string['classjournal:addinstance'] = 'Добавлять новый журнал занятий';
$string['classjournal:grade'] = 'Выставлять оценки за занятия';
$string['classjournal:manage'] = 'Управлять занятиями журнала';
$string['classjournal:view'] = 'Просматривать журнал занятий';
$string['classjournal:viewallgrades'] = 'Просматривать все оценки журнала';
$string['clearfilters'] = 'Очистить';
$string['comment'] = 'Комментарий';
$string['confirmbulkdeletelessons'] = 'Удалить выбранные занятия ({$a}) и все их оценки?';
$string['confirmdeletelesson'] = 'Удалить занятие "{$a}" и все его оценки?';
$string['csvfile'] = 'Файл Excel или CSV';
$string['datefrom'] = 'Дата с';
$string['dateto'] = 'Дата по';
$string['deletelesson'] = 'Удалить занятие';
$string['deleteselectedlessons'] = 'Удалить выбранные занятия';
$string['description'] = 'Описание';
$string['editlesson'] = 'Редактировать занятие';
$string['emptygradeszero'] = 'Считать пустые оценки как ноль';
$string['emptygradeszero_help'] = 'Если включено, занятия без оценки участвуют в итоговом расчете как 0. Если выключено, пустые оценки игнорируются, поэтому среднее считается только по занятиям, где оценка уже выставлена.';
$string['exportcsv'] = 'Экспорт в Excel';
$string['fillcolumn'] = 'Заполнить';
$string['grade'] = 'Оценка';
$string['gradebookmax'] = 'Максимум итоговой оценки в Moodle';
$string['gradebookmax_help'] = 'Максимальное значение единственного итогового элемента в журнале оценок Moodle. Внутри занятий можно использовать максимумы 5, 10 или 100, а итоговую колонку Moodle показывать в фиксированной шкале, например 100.';
$string['grades'] = 'Оценки';
$string['gradetype'] = 'Оценивание';
$string['gradetype_help'] = 'Выберите, как оценивается это занятие:

* **Баллы** — преподаватель вводит число от 0 до заданного максимума.
* **Шкала** — преподаватель выбирает один из именованных вариантов шкалы Moodle.

В обоих случаях в журнале оценок остаётся **одна** числовая итоговая колонка по всему журналу. Вариант шкалы переводится в свою долю (например, 3-й из 4 вариантов = 75%) и добавляется в итог как обычное занятие — вторая оценка не создаётся.

Варианты для шкалы берутся из шкал, заданных в разделе *Управление курсом → Оценки → Шкалы*.';
$string['gradetypepoint'] = 'Баллы';
$string['gradetypescale'] = 'Шкала';
$string['import'] = 'Импорт';
$string['importbadformat'] = 'Не удалось прочитать файл. Используйте файл, выгруженный на этой странице, сохранив столбец «userid» и столбцы занятий.';
$string['importcsv'] = 'Импорт из файла';
$string['importdone'] = 'Обновлено оценок: {$a->written}, пропущено строк: {$a->skipped}.';
$string['importhelp'] = 'Загрузите файл Excel (.xlsx) или CSV, выгруженный на этой странице. Сохраните столбец «userid» и заголовки занятий (каждый оканчивается на [#id]). Значение ставит оценку, пустая ячейка — оценки нет. Комментарии не меняются.';
$string['invalidgrade'] = 'Оценка должна быть от 0 до {$a}.';
$string['lesson'] = 'Занятие';
$string['lessondate'] = 'Дата занятия';
$string['lessonname'] = 'Название занятия';
$string['lessons'] = 'Занятия';
$string['lessonsdeleted'] = 'Выбранные занятия удалены.';
$string['maxgrade'] = 'Максимальная оценка';
$string['modulename'] = 'Журнал занятий';
$string['modulenameplural'] = 'Журналы занятий';
$string['nogrades'] = 'Оценки пока не выставлены.';
$string['nolessons'] = 'Занятий пока нет.';
$string['perpage'] = 'На странице';
$string['pluginadministration'] = 'Администрирование журнала занятий';
$string['pluginname'] = 'Журнал занятий';
$string['privacy:metadata:classjournal_grades'] = 'Хранит оценки и комментарии к занятиям журнала.';
$string['privacy:metadata:classjournal_grades:comment'] = 'Необязательный комментарий преподавателя.';
$string['privacy:metadata:classjournal_grades:grade'] = 'Оценка за занятие.';
$string['privacy:metadata:classjournal_grades:timemodified'] = 'Время последнего изменения оценки.';
$string['privacy:metadata:classjournal_grades:userid'] = 'Пользователь, которому выставлена оценка.';
$string['repeatcount'] = 'Количество создаваемых занятий';
$string['repeatinterval'] = 'Повторять каждые N недель';
$string['savegrades'] = 'Сохранить оценки';
$string['scale'] = 'Шкала';
$string['scale_help'] = 'Варианты оценки — это деления выбранной шкалы, от меньшего к большему. Шкалы настраиваются в разделе *Управление курсом → Оценки → Шкалы*. Выбранный вариант сохраняется и переводится в процент шкалы, формируя единую числовую итоговую оценку журнала в журнале оценок.';
$string['searchlessons'] = 'Поиск занятий';
$string['selectlesson'] = 'Выбрать занятие {$a}';
$string['showallgrades'] = 'Показывать студентам чужие оценки';
$string['showallgrades_help'] = 'Если включено, студенты смогут видеть оценки других студентов. Для приватного журнала оставьте настройку выключенной.';
$string['total'] = 'Итого';
$string['visiblelessonssaved'] = 'На этой странице сохраняются оценки только для занятий, которые сейчас показаны фильтрами и пагинацией.';
