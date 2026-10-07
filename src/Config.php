<?php

namespace GlpiPlugin\Os;

use CommonGLPI;
use Config as GlpiConfig;
use Html;

/**
 * Configuração geral do plugin (dados da empresa + logotipo).
 * Os valores ficam em glpi_configs, contexto "plugin:os".
 */
class Config extends CommonGLPI
{
    public const CONTEXT = 'plugin:os';

    public static $rightname = 'plugin_os';

    public static function getTypeName($nb = 0): string
    {
        return __('Ordem de Serviço', 'os');
    }

    /** Campos configuráveis => rótulo */
    public static function getFields(): array
    {
        return [
            'name'    => __('Nome da empresa', 'os'),
            'cnpj'    => __('CNPJ', 'os'),
            'address' => __('Endereço', 'os'),
            'city'    => __('Cidade', 'os'),
            'phone'   => __('Telefone', 'os'),
            'site'    => __('Site', 'os'),
        ];
    }

    public static function getDefaults(): array
    {
        return array_fill_keys(array_keys(self::getFields()), '');
    }

    public static function getValues(): array
    {
        return array_merge(self::getDefaults(), GlpiConfig::getConfigurationValues(self::CONTEXT));
    }

    public static function save(array $input): void
    {
        $values = [];
        foreach (array_keys(self::getFields()) as $key) {
            $value = trim(strip_tags((string) ($input[$key] ?? '')));
            $values[$key] = mb_substr($value, 0, 255);
        }
        GlpiConfig::setConfigurationValues(self::CONTEXT, $values);
    }

    // ---- Logotipo ----------------------------------------------------------

    public static function getCustomLogoPath(): string
    {
        return GLPI_PLUGIN_DOC_DIR . '/os/logo_os.png';
    }

    public static function getLogoPath(): string
    {
        $custom = self::getCustomLogoPath();
        return is_file($custom) ? $custom : dirname(__DIR__) . '/pics/logo_os.png';
    }

    /**
     * Valida a imagem enviada e a regrava como PNG (sem canal alfa),
     * descartando qualquer conteúdo que não seja imagem de verdade.
     */
    public static function saveLogo(array $file): bool
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            return false;
        }
        $info = @getimagesize($file['tmp_name']);
        if ($info === false || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF], true)) {
            return false;
        }
        $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if ($src === false) {
            return false;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $dst = imagecreatetruecolor($w, $h);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);

        $target = self::getCustomLogoPath();
        if (!is_dir(dirname($target)) && !@mkdir(dirname($target), 0755, true) && !is_dir(dirname($target))) {
            return false;
        }
        return imagepng($dst, $target);
    }

    public static function deleteLogo(): void
    {
        $custom = self::getCustomLogoPath();
        if (is_file($custom)) {
            @unlink($custom);
        }
    }

    // ---- Formulário --------------------------------------------------------

    public static function showForm(): void
    {
        global $CFG_GLPI;

        $canedit = \Session::haveRight(self::$rightname, UPDATE);
        $values  = self::getValues();
        $action  = $CFG_GLPI['root_doc'] . '/plugins/os/front/config.form.php';
        $logo    = $CFG_GLPI['root_doc'] . '/plugins/os/front/logo.php';

        echo "<div class='container-fluid'>";
        echo "<form method='post' action='" . htmlspecialchars($action, ENT_QUOTES) . "'>";
        echo "<div class='card mb-3'><div class='card-header'><h3 class='card-title'>"
            . __('Dados da empresa exibidos na O.S.', 'os') . "</h3></div><div class='card-body'>";
        foreach (self::getFields() as $key => $label) {
            echo "<div class='mb-3'><label class='form-label' for='os_$key'>" . htmlspecialchars($label) . "</label>";
            echo "<input type='text' class='form-control' id='os_$key' name='$key' maxlength='255' value='"
                . htmlspecialchars($values[$key], ENT_QUOTES) . "'" . ($canedit ? '' : ' disabled') . ">";
            echo "</div>";
        }
        echo "</div>";
        if ($canedit) {
            echo "<div class='card-footer text-center'>"
                . Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary'])
                . "</div>";
        }
        echo "</div>";
        Html::closeForm();

        // Logotipo
        echo "<div class='card'><div class='card-header'><h3 class='card-title'>"
            . __('Logotipo da O.S.', 'os') . "</h3></div><div class='card-body'>";
        echo "<p class='text-muted'>" . __('Envie uma imagem PNG, JPG ou GIF (recomendado 300x100 px, até 2 MB).', 'os') . "</p>";
        if ($canedit) {
            echo "<form method='post' enctype='multipart/form-data' action='" . htmlspecialchars($logo, ENT_QUOTES) . "'>";
            echo "<input type='file' class='form-control mb-3' name='logo' accept='image/png,image/jpeg,image/gif' required>";
            echo Html::submit(__('Enviar logotipo', 'os'), ['name' => 'upload', 'class' => 'btn btn-primary']);
            Html::closeForm();
            if (is_file(self::getCustomLogoPath())) {
                echo "<form method='post' action='" . htmlspecialchars($logo, ENT_QUOTES) . "' class='mt-2'>";
                echo Html::submit(__('Restaurar logotipo padrão', 'os'), ['name' => 'delete', 'class' => 'btn btn-outline-secondary']);
                Html::closeForm();
            }
        }
        echo "</div></div></div>";
    }
}
