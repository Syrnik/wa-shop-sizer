<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2026
 */

/**
 * Подставная модель настроек плагина — без соединения с БД.
 *
 * Пустой конструктор перекрывает waModel::__construct(), которая иначе открывает соединение с
 * БД через waDbConnector. Переопределены ровно те методы waAppSettingsModel, которые вызывает
 * waPlugin::getSettings()/saveSettings(): get(), set(), del(), clearCache(). Сигнатуры — как у
 * родителя. Всё остальное (например прямой SQL) не переопределено и упадёт громко, если
 * когда-нибудь понадобится — сигнал, что фейк нужно расширить, а не тихо давать не то поведение.
 *
 * set() пишет ровно то значение, что получила (включая уже сериализованный в JSON массив) —
 * так тест видит, что именно улетело бы в БД.
 */
class shopSizerPluginTestAppSettingsModelFake extends waAppSettingsModel
{
    /** @var array */
    public $stored = [];

    /** @var array Всё, что записывалось через set()/del(), в порядке вызовов. */
    public $log = [];

    public function __construct()
    {
    }

    public function get($app_id, $name = null, $default = '')
    {
        if ($name === null) {
            return $this->stored;
        }

        return array_key_exists($name, $this->stored) ? $this->stored[$name] : $default;
    }

    public function set($app_id, $name, $value)
    {
        $this->stored[$name] = $value;
        $this->log[] = ['set', $name, $value];

        return true;
    }

    public function del($app_id, $name = null)
    {
        if ($name === null) {
            $this->stored = [];
        } else {
            unset($this->stored[$name]);
        }
        $this->log[] = ['del', $name];

        return true;
    }

    public function clearCache($app_id, $only_file = false)
    {
    }
}
