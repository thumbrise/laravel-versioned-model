<?php

declare(strict_types=1);

namespace Thumbrise\LaravelVersionedModel\Tests\Fixtures;

use Thumbrise\LaravelVersionedModel\Traits\HasVersions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TestModel extends Model
{
    use HasVersions;

    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'email',
        'status',
        'count',
    ];

    public static function createTable(): void
    {
        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('status')->nullable();
            $table->integer('count')->default(0);
            $table->timestamps();
        });
    }

    public static function dropTable(): void
    {
        Schema::dropIfExists('test_models');
    }
}
