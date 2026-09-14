<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2026
 */

/**
 * Тестовый дубль shopSizerPlugin.
 *
 * Оригинальный конструктор (waPlugin::__construct()) вызывает checkUpdates(), которая на первом
 * запуске лезет в БД за update_time и может выполнить install()/миграции плагина — не нужно
 * в юнит-тестах. checkUpdates() здесь пустая, сигнатура родителя (без объявленного типа
 * возврата) не меняется.
 *
 * getSettings() смотрит в protected $settings и, если оно уже не null, в БД не идёт и дефолты
 * из lib/config/settings.php не подмешивает — поэтому setTestSettings() должен получать
 * полностью заполненный массив настроек (см. tests/shopSizerPluginShippingPackageTest.php).
 */
class shopSizerPluginTestDouble extends shopSizerPlugin
{
    public function __construct()
    {
        parent::__construct(['id' => 'sizer', 'app_id' => 'shop']);
    }

    protected function checkUpdates()
    {
    }

    public function setTestSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * Подменяет статическую модель настроек (waPlugin::$app_settings_model), используемую
     * getSettings()/saveSettings() при обращении к БД. Статика общая на процесс — вызывающий
     * тест обязан вернуть её в null через resetSettingsModel() в tearDown(). Свойство protected,
     * но доступно напрямую как self::$app_settings_model — дубль наследуется от waPlugin.
     *
     * @param waAppSettingsModel $model
     */
    public static function installSettingsModel(waAppSettingsModel $model): void
    {
        self::$app_settings_model = $model;
    }

    public static function resetSettingsModel(): void
    {
        self::$app_settings_model = null;
    }
}
