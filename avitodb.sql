--
-- Структура таблицы `avito_adv`
--

CREATE TABLE `avito_adv` (
  `id` int(11) NOT NULL,
  `avito_id` bigint(11) UNSIGNED NOT NULL,
  `city_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `datatime_creation` datetime NOT NULL,
  `datatime_update` datetime NOT NULL,
  `status_id` int(11) NOT NULL,
  `category_url` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Структура таблицы `avito_stats`
--

CREATE TABLE `avito_stats` (
  `id` int(11) NOT NULL,
  `datatime` datetime NOT NULL,
  `avito_adv_id` int(11) NOT NULL,
  `views` int(11) NOT NULL,
  `position` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `avito_adv`
--
ALTER TABLE `avito_adv`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `avito_id` (`avito_id`);

--
-- Индексы таблицы `avito_stats`
--
ALTER TABLE `avito_stats`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `avito_adv`
--
ALTER TABLE `avito_adv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `avito_stats`
--
ALTER TABLE `avito_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;