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

$string['pluginname'] = 'Журнал занятий';
$string['modulename'] = 'Журнал занятий';
$string['modulenameplural'] = 'Журналы занятий';
$string['pluginadministration'] = 'Администрирование журнала занятий';
$string['classjournal:addinstance'] = 'Добавлять новый журнал занятий';
$string['classjournal:view'] = 'Просматривать журнал занятий';
$string['classjournal:manage'] = 'Управлять занятиями журнала';
$string['classjournal:grade'] = 'Выставлять оценки за занятия';
$string['classjournal:viewallgrades'] = 'Просматривать все оценки журнала';
$string['aggregation'] = 'Расчет итоговой оценки';
$string['aggregation_help'] = 'Определяет, как итог считается в журнале, журнале оценок Moodle и API. Сумма складывает баллы занятий и ограничивает результат максимумом итогового элемента Moodle. Среднее считает средний процент по занятиям и переводит его в заданный максимум итогового элемента.';
$string['aggregationsum'] = 'Сумма';
$string['aggregationavg'] = 'Среднее';
$string['aggregationsumdescription'] = 'Итоговая оценка: баллы занятий суммируются, пустые оценки игнорируются, результат ограничивается значением {$a} в журнале оценок Moodle.';
$string['aggregationsumzerodescription'] = 'Итоговая оценка: баллы занятий суммируются, пустые оценки считаются как 0, результат ограничивается значением {$a} в журнале оценок Moodle.';
$string['aggregationavgdescription'] = 'Итоговая оценка: Moodle считает средний процент по занятиям с выставленными оценками и переводит его в шкалу {$a}. Пустые оценки игнорируются.';
$string['aggregationavgzerodescription'] = 'Итоговая оценка: Moodle считает средний процент по всем занятиям и переводит его в шкалу {$a}. Пустые оценки считаются как 0.';
$string['emptygradeszero'] = 'Считать пустые оценки как ноль';
$string['emptygradeszero_help'] = 'Если включено, занятия без оценки участвуют в итоговом расчете как 0. Если выключено, пустые оценки игнорируются, поэтому среднее считается только по занятиям, где оценка уже выставлена.';
$string['gradebookmax'] = 'Максимум итоговой оценки в Moodle';
$string['gradebookmax_help'] = 'Максимальное значение единственного итогового элемента в журнале оценок Moodle. Внутри занятий можно использовать максимумы 5, 10 или 100, а итоговую колонку Moodle показывать в фиксированной шкале, например 100.';
$string['showallgrades'] = 'Показывать студентам чужие оценки';
$string['showallgrades_help'] = 'Если включено, студенты смогут видеть оценки других студентов. Для приватного журнала оставьте настройку выключенной.';
$string['visiblelessonssaved'] = 'На этой странице сохраняются оценки только для занятий, которые сейчас показаны фильтрами и пагинацией.';
$string['lesson'] = 'Занятие';
$string['lessons'] = 'Занятия';
$string['addlesson'] = 'Добавить занятие';
$string['editlesson'] = 'Редактировать занятие';
$string['deletelesson'] = 'Удалить занятие';
$string['lessonname'] = 'Название занятия';
$string['lessondate'] = 'Дата занятия';
$string['maxgrade'] = 'Максимальная оценка';
$string['description'] = 'Описание';
$string['grades'] = 'Оценки';
$string['grade'] = 'Оценка';
$string['comment'] = 'Комментарий';
$string['savegrades'] = 'Сохранить оценки';
$string['repeatcount'] = 'Количество создаваемых занятий';
$string['repeatinterval'] = 'Повторять каждые N недель';
$string['searchlessons'] = 'Поиск занятий';
$string['datefrom'] = 'Дата с';
$string['dateto'] = 'Дата по';
$string['perpage'] = 'На странице';
$string['applyfilters'] = 'Применить';
$string['clearfilters'] = 'Очистить';
$string['selectlesson'] = 'Выбрать занятие {$a}';
$string['deleteselectedlessons'] = 'Удалить выбранные занятия';
$string['confirmbulkdeletelessons'] = 'Удалить выбранные занятия ({$a}) и все их оценки?';
$string['lessonsdeleted'] = 'Выбранные занятия удалены.';
$string['nogrades'] = 'Оценки пока не выставлены.';
$string['nolessons'] = 'Занятий пока нет.';
$string['total'] = 'Итого';
$string['confirmdeletelesson'] = 'Удалить занятие "{$a}" и все его оценки?';
$string['invalidgrade'] = 'Оценка должна быть от 0 до {$a}.';
$string['privacy:metadata:classjournal_grades'] = 'Хранит оценки и комментарии к занятиям журнала.';
$string['privacy:metadata:classjournal_grades:userid'] = 'Пользователь, которому выставлена оценка.';
$string['privacy:metadata:classjournal_grades:grade'] = 'Оценка за занятие.';
$string['privacy:metadata:classjournal_grades:comment'] = 'Необязательный комментарий преподавателя.';
$string['privacy:metadata:classjournal_grades:timemodified'] = 'Время последнего изменения оценки.';
