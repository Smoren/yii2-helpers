<?php

namespace Smoren\Yii2\Helpers;

use PhpSchool\CliMenu\Action\GoBackAction;
use PhpSchool\CliMenu\Builder\CliMenuBuilder;
use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\Exception\InvalidTerminalException;
use Yii;
use yii\console\Controller;
use yii\helpers\FileHelper;

/**
 * Контроллер консольного меню
 */
class MenuHelper
{
    /**
     * Запуск меню
     * @throws InvalidTerminalException
     */
    public static function runMenu(Controller $controller)
    {
        $projectModules = static::getProjectModules($controller);

        $menu = (new CliMenuBuilder)
            ->setTitle('Yii2 CLI Menu')
            ->addLineBreak('-')
            ->setBorder(1, 2, 'blue')
            ->setPadding(2, 4)
            ->setMarginAuto()
            ->addSubMenu('Create Migration', function(CliMenuBuilder $b) use ($projectModules) {
                $b->disableDefaultItems()
                    ->setTitle('Choose Module:')
                    ->addItems($projectModules)
                    ->addItem('Return to parent menu', new GoBackAction);
            })
            ->build();

        $menu->open();
    }

    /**
     * Получает все модули в проекте
     * @param Controller $controller
     * @return array
     */
    protected static function getProjectModules(Controller $controller): array
    {
        $allFiles = FileHelper::findDirectories(Yii::getAlias('@app/modules'), ['recursive' => false]);
        $result = [];

        sort($allFiles);
        foreach($allFiles as $dirPath) {
            $result[] = [basename($dirPath), function(CliMenu $menu) use ($controller) {

                $migrationName = $menu->askText()
                    ->setPromptText('Enter migration name')
                    ->setPlaceholderText('')
                    ->setValidationFailedText('Please enter migration name')
                    ->ask()
                    ->fetch();

                $menu->close();
                $moduleName = $menu->getSelectedItem()->getText();

                static::runCommand($controller, 'migrate/create', [$migrationName, 'migrationPath' => "@app/modules/{$moduleName}/migrations"]);
            }];
        }

        return $result;
    }

    /**
     * Запускает команду
     * @param Controller $controller
     * @param string $route
     * @param array $params
     */
    protected static function runCommand(Controller $controller, string $route, array $params)
    {
        $controller->run($route, $params);
    }
}