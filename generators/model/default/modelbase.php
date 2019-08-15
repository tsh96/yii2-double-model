<?php
/**
 * This is the template for generating the model class of a specified table.
 */
use yii\helpers\Inflector;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\model\Generator */
/* @var $tableName string full table name */
/* @var $className string class name */
/* @var $tableSchema yii\db\TableSchema */
/* @var $labels string[] list of attribute labels (name => label) */
/* @var $rules string[] list of validation rules */
/* @var $relations array list of relations (name => relation declaration) */

echo "<?php\n";
?>

namespace <?= $generator->ns ?>\base;

use Yii;
<?php
$hasDone = [];
$classNameSpaceSearch = [
    'common\models', 'frontend\models', 'common\modules\service\models', 'common\modules\user\models'
];
foreach ($relations as $name => $relation) {
    if (!in_array($relation[1], $hasDone)) {
        if(class_exists("$generator->ns\\$relation[1]")) {
            echo "use $generator->ns\\$relation[1];\n";
        } else {
            //Searching class location
            $found = false;
            foreach($classNameSpaceSearch as $namespacePath) {
                if(class_exists("$namespacePath\\$relation[1]")) {
                    $found = true;
                    echo "use $namespacePath\\$relation[1];\n";
                    break;
                }
            }
            if(!$found) {
                Yii::error('Cannot find namespace name for relation '.$relation[1]);
            }
        }

    }
    $hasDone[] = $relation[1];
}
?>

/**
 * This is the model class for table "<?= $tableName ?>".
 *
<?php foreach ($tableSchema->columns as $column): ?>
 * @property <?= "{$column->phpType} \${$column->name}\n" ?>
<?php endforeach; ?>
<?php if (!empty($relations)): ?>
 *
<?php foreach ($relations as $name => $relation): ?>
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name) . "\n" ?>
<?php endforeach; ?>
<?php endif; ?>
 */
class <?= $className ?>Base extends <?= '\\' . ltrim($generator->baseClass, '\\') . "\n" ?>
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '<?= $generator->generateTableName($tableName) ?>';
    }
    <?php if ($generator->db !== 'db'): ?>

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('<?= $generator->db ?>');
    }
    <?php endif; ?>

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [<?= "\n            " . implode(",\n            ", $rules) . "\n        " ?>];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
        <?php foreach ($labels as $name => $label): ?>
    <?= "'$name' => " . $generator->generateString($label) . ",\n" ?>
        <?php endforeach; ?>
];
    }
    <?php foreach ($relations as $name => $relation): ?>

    /**
     * @return \yii\db\ActiveQuery
     */
    public function get<?= $name ?>()
    {
        <?= $relation[0] . "\n" ?>
    }
<?php endforeach; ?>

}
