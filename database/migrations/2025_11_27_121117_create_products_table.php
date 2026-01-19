<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Liên kết với categories.id (BigInt)
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');

            $table->string('name');
            $table->string('slug');
            $table->string('thumbnail');
            $table->longText('content');
            $table->text('description')->nullable();
            $table->decimal('price_buy', 12, 0); // 12 số, 0 số thập phân (VND)

            $table->boolean('is_new')->default(false); // Mặc định là false (0). Logic ngày tháng sẽ tự lo việc hiển thị NEW.
            $table->boolean('is_sale')->default(false); // Mặc định không giảm giá

            $table->unsignedInteger('created_by')->default(1);
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
