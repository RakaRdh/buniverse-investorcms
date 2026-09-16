-- ==============================================================================
-- DDL MIGRATION SCRIPT: Schema Fixes & Typo Correction
-- Database Target: db_investorcms / investor
-- Referensi: Audit Migration V2 (Prioritas 4 & Prioritas 2/3 Data Migrations)
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- 1. Koreksi Anomali Kolom `saham_topvalue`
-- Deskripsi: Menyelaraskan struktur kolom saham_topvalue agar sesuai dengan metrik
--            perdagangan saham legacy (prev, close, changeVal, percentage, volume, val)
-- ------------------------------------------------------------------------------
-- ALTER TABLE `saham_topvalue`
--   CHANGE COLUMN IF EXISTS `composite` `prev` DECIMAL(12,2) NULL DEFAULT 0,
--   CHANGE COLUMN IF EXISTS `chg` `changeVal` DECIMAL(12,2) NULL DEFAULT 0,
--   CHANGE COLUMN IF EXISTS `pct` `percentage` DECIMAL(6,2) NULL DEFAULT 0;

-- ------------------------------------------------------------------------------
-- 2. Koreksi Typo Kolom `tbltrending`
-- Deskripsi: Memperbaiki nama kolom typo `modifiedData` menjadi `modifiedDate`
-- ------------------------------------------------------------------------------
ALTER TABLE `tbltrending` 
  CHANGE COLUMN `modifiedData` `modifiedDate` DATETIME NULL DEFAULT NULL;

-- ------------------------------------------------------------------------------
-- 3. Koreksi Typo Kolom `tblsaham_summary`
-- Deskripsi: Memperbaiki typo nama kolom ListedShare & NonRegulerVolume
-- ------------------------------------------------------------------------------
ALTER TABLE `tblsaham_summary` 
  CHANGE COLUMN `ListedShare` `ListedShares` BIGINT NULL DEFAULT 0,
  CHANGE COLUMN `NonRegulerVolume` `NonRegularVolume` BIGINT NULL DEFAULT 0;

-- ------------------------------------------------------------------------------
-- 4. Koreksi Kolom & Typo `tblchartdata`
-- Deskripsi: Menambahkan kolom yAxisTitle & yAxisData, serta typo updateAt/updateBy
-- ------------------------------------------------------------------------------
ALTER TABLE `tblchartdata` 
  ADD COLUMN IF NOT EXISTS `yAxisTitle` VARCHAR(255) NULL AFTER `title`,
  ADD COLUMN IF NOT EXISTS `yAxisData` TEXT NULL AFTER `xAxisData`,
  CHANGE COLUMN `updateAt` `updatedAt` DATETIME NULL DEFAULT NULL,
  CHANGE COLUMN `updateBy` `updatedBy` VARCHAR(100) NULL DEFAULT NULL;

-- ------------------------------------------------------------------------------
-- 5. Standardisasi Casing Kolom `articletag` & `tblcommentar`
-- Deskripsi: Menyelaraskan kolom foreign key articleId -> articleID
-- ------------------------------------------------------------------------------
ALTER TABLE `articletag` 
  CHANGE COLUMN `articleId` `articleID` INT UNSIGNED NOT NULL;

-- ALTER TABLE `tblcommentar` 
--   CHANGE COLUMN `articleId` `articleID` INT UNSIGNED NOT NULL;

-- ------------------------------------------------------------------------------
-- 6. DATA MIGRATION: Inversi Kolom Category (Prioritas 2 - Opsi A yang Disetujui)
-- Deskripsi: Menukar nilai categoryName (menjadi slug) dan label (menjadi nama tampilan)
--            agar konsisten 100% dengan skema legacy Investor.id
-- ------------------------------------------------------------------------------
UPDATE `category` 
SET `categoryName` = (@temp := `categoryName`),
    `categoryName` = `label`,
    `label` = @temp;

-- ------------------------------------------------------------------------------
-- 7. DATA MIGRATION: Normalisasi Kategori Headline (Prioritas 3)
-- Deskripsi: Mengubah kategori slot headline 'headline' menjadi 'home'
-- ------------------------------------------------------------------------------
UPDATE `articleheadline` 
SET `category` = 'home' 
WHERE `category` = 'headline';

-- ------------------------------------------------------------------------------
-- 8. DATA MIGRATION: Bersihkan Polusi Metakey 'point' pada tblarticlemeta
-- Deskripsi: Menghapus metakey 'point' dari tblarticlemeta karena data points
--            sudah dikelola murni pada tabel mandiri `articlepoints`.
-- ------------------------------------------------------------------------------
DELETE FROM `articlemeta` WHERE `metakey` = 'point';
