--
-- Database: `63_intermesh_dev`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `test_a`
--

CREATE TABLE `test_a` (
  `id` int(11) NOT NULL,
  `propA` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `createdAt` datetime NOT NULL,
  `modifiedAt` datetime NOT NULL,
	`deletedAt` datetime DEFAULT NULL,
  `modSeq` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `test_a_has_many`
--

CREATE TABLE `test_a_has_many` (
  `id` int(11) NOT NULL,
  `aId` int(11) NOT NULL,
  `propOfHasManyA` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `test_a_has_one`
--

CREATE TABLE `test_a_has_one` (
  `id` int(11) NOT NULL,
  `aId` int(11) NOT NULL,
  `propA` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `test_b`
--

CREATE TABLE `test_b` (
  `id` int(11) NOT NULL,
  `propB` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `test_c`
--

CREATE TABLE `test_c` (
  `id` int(11) NOT NULL,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modSeq` int(11) NOT NULL,  
	`deletedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `test_a`
--
ALTER TABLE `test_a`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updatedModSeq` (`modSeq`);

--
-- Indexen voor tabel `test_a_has_many`
--
ALTER TABLE `test_a_has_many`
  ADD PRIMARY KEY (`id`,`aId`),
  ADD KEY `aId` (`aId`);

--
-- Indexen voor tabel `test_a_has_one`
--
ALTER TABLE `test_a_has_one`
  ADD PRIMARY KEY (`id`,`aId`),
  ADD KEY `aId` (`aId`);

--
-- Indexen voor tabel `test_b`
--
ALTER TABLE `test_b`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cId` (`cId`);

--
-- Indexen voor tabel `test_c`
--
ALTER TABLE `test_c`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modSeq` (`modSeq`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `test_a`
--
ALTER TABLE `test_a`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
--
-- AUTO_INCREMENT voor een tabel `test_a_has_many`
--
ALTER TABLE `test_a_has_many`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;
--
-- AUTO_INCREMENT voor een tabel `test_a_has_one`
--
ALTER TABLE `test_a_has_one`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT voor een tabel `test_c`
--
ALTER TABLE `test_c`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;
--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `test_a_has_many`
--
ALTER TABLE `test_a_has_many`
  ADD CONSTRAINT `test_a_has_many_ibfk_1` FOREIGN KEY (`aId`) REFERENCES `test_a` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `test_a_has_one`
--
ALTER TABLE `test_a_has_one`
  ADD CONSTRAINT `test_a_has_one_ibfk_1` FOREIGN KEY (`aId`) REFERENCES `test_a` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `test_b`
--
ALTER TABLE `test_b`
  ADD CONSTRAINT `test_b_ibfk_1` FOREIGN KEY (`id`) REFERENCES `test_a` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `test_b_ibfk_2` FOREIGN KEY (`cId`) REFERENCES `test_c` (`id`) ON DELETE SET NULL;
