# dots

Проект посвящен геолокационной игре по типу игры "в точки" на листике в клеточку разными ручками.

## Правила

Правила формируются исходя из игрового процесса, обсуждаются в последствии.

## Этапы

- Формирование ТЗ в общем виде
- Формирование нужных структур данных и СУБД.
  - Пользователи
  - Игры
  - Точки на карте
  - Пути пользователей на карте (состоят из точек)
  - додумайте
- Подготовка скелета приложения:
  - Логины, Регистрация, Личный кабинет
  - Карта и отрисовка полигонов на ней разными полупрозрачными цветами.
  - Другие игроки на карте.
  - Сбор и отсылка координат пользователя на сервер.
  Всё это заготовить как "конструктор", из которого в последствии собрать приложение
- Alpha версия
- Итеративная доводка, правка всего что нужно поправить, формирования правил.

## Инструменты

- Leaflet js
- geolocation API (нужен https, будем думать)
- yii2
- Интерактивный интерфейс, шаблонизация полностью или частично на клиенте.
- jQuery/nanoBind/angular/whatever you need
- git
- gogs:
  - VCS
  - Milestones (этапы разработки)
  - Issues (багрепорты и фичреквесты).
