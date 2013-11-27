testapp
=======

Программа демонстрирует взаимосвязь используемых нами технологий
разработки web-приложений - php xml xsd xslt.

Схема обработки запросов следующая:
(xml запрос) -> (php приложение: проверка входных данных на соответсвие
схеме xsd, обработка) -> (xml ответ) -> (xslt преобразование: форма в
браузере) -> (xml запрос) ->....

Ваше задание:
1) установить тестовую базу (скрипты в tools/sql/)
2) установить программу на web-сервер (для тестирования откройте в
браузере testapp/web/documentList.php) - данный отчет представляет собой
простой фильтр документов по дате создания
2) разобраться с технологией обработки запросов на основе данной программы
3) модифицировать программу, добавив в отчет поля ключевые слова (поле
keywords), удален (да/нет) (поле deleted)
4) модифицировать программу, добавив в форму фильтр по полю наименование
(содержит)

Установленное приложение в openshift
https://testapp-techgoogleinfo.rhcloud.com/testapp/web/documentList.php