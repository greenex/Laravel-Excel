<?php

namespace greenex\Excel;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use greenex\Excel\Files\Filesystem;
use greenex\Excel\Mixins\StoreCollection;
use greenex\Excel\Console\ExportMakeCommand;
use greenex\Excel\Console\ImportMakeCommand;
use greenex\Excel\Mixins\DownloadCollection;
use greenex\Excel\Files\TemporaryFileFactory;
use Laravel\Lumen\Application as LumenApplication;
use greenex\Excel\Transactions\TransactionHandler;
use greenex\Excel\Transactions\TransactionManager;

class ExcelServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            if ($this->app instanceof LumenApplication) {
                $this->app->configure('excel2');
            } else {
                $this->publishes([
                    $this->getConfigFile() => config_path('excel2.php'),
                ], 'config');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->mergeConfigFrom(
            $this->getConfigFile(),
            'excel2'
        );

        $this->app->bind(TransactionManager::class, function () {
            return new TransactionManager($this->app);
        });

        $this->app->bind(TransactionHandler::class, function () {
            return $this->app->make(TransactionManager::class)->driver();
        });

        $this->app->bind(TemporaryFileFactory::class, function () {
            return new TemporaryFileFactory(
                config('excel2.temporary_files.local_path', config('excel2.exports.temp_path', storage_path('framework/laravel-excel'))),
                config('excel2.temporary_files.remote_disk')

            );
        });

        $this->app->bind(Filesystem::class, function () {
            return new Filesystem($this->app->make('filesystem'));
        });

        $this->app->bind('excel2', function () {
            return new Excel(
                $this->app->make(Writer::class),
                $this->app->make(QueuedWriter::class),
                $this->app->make(Reader::class),
                $this->app->make(Filesystem::class)
            );
        });

        $this->app->alias('excel2', Excel::class);
        $this->app->alias('excel2', Exporter::class);
        $this->app->alias('excel2', Importer::class);

        Collection::mixin(new DownloadCollection);
        Collection::mixin(new StoreCollection);

        $this->commands([
            ExportMakeCommand::class,
            ImportMakeCommand::class,
        ]);
    }

    /**
     * @return string
     */
    protected function getConfigFile(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'excel2.php';
    }
}
