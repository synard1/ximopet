<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('ternaks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaksi_id')->nullable();
            $table->uuid('standar_bobot_id')->nullable();
            $table->uuid('farm_id');
            $table->uuid('coop_id');
            $table->string('name'); //name for batch code / periode
            $table->string('breed'); //jenis
            $table->dateTime('start_date'); //tanggal mulai
            $table->dateTime('end_date')->nullable();
            $table->integer('populasi_awal'); //jumlah awal
            $table->decimal('berat_awal', 10, 2)->default(0); //berat beli rata - rata
            $table->decimal('harga', 10, 2);        // Harga per unit saat beli
            $table->string('pic')->nullable();
            $table->json('data')->nullable();
            $table->string('status'); //status
            $table->string('keterangan')->nullable(); //keterangan
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->foreign('transaksi_id')->references('id')->on('transaksi_beli')->onDelete('cascade');
            $table->foreign('standar_bobot_id')->references('id')->on('standar_bobots')->onDelete('cascade');
        });


        // Tambah tabel baru untuk tracking transaksi ternak
        Schema::create('transaksi_ternak', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelompok_ternak_id');
            $table->string('jenis_transaksi');
            $table->dateTime('tanggal');
            $table->uuid('farm_id');
            $table->uuid('coop_id');

            // Data Kuantitas
            $table->integer('quantity');          // Jumlah saat ini
            $table->decimal('berat_total', 10, 2); // Estimasi berat total
            $table->decimal('berat_rata', 10, 2);   // Rata-rata berat per ekor

            // Data Finansial 
            $table->decimal('harga_satuan', 10, 2); // Estimasi berat total
            $table->decimal('total_harga', 20, 2);   // Rata-rata berat per ekor

            // Untuk Mutasi
            $table->uuid('farm_tujuan_id')->nullable();
            $table->uuid('kandang_tujuan_id')->nullable();

            //Untuk Penjualan
            $table->uuid('pembeli_id')->nullable();

            $table->string('status');              // active, sold, dead, culled
            $table->string('keterangan'); //keterangan

            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('kelompok_ternak_id')->references('id')->on('ternaks');
            $table->foreign('farm_tujuan_id')->references('id')->on('farms');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('kandang_tujuan_id')->references('id')->on('coops');
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->foreign('pembeli_id')->references('id')->on('partners');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Tambah tabel baru untuk tracking current state
        Schema::create('current_ternaks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelompok_ternak_id');
            $table->uuid('farm_id');
            $table->uuid('coop_id');
            $table->integer('quantity');          // Jumlah saat ini
            $table->decimal('berat_total', 10, 2); // Estimasi berat total
            $table->decimal('avg_berat', 10, 2);   // Rata-rata berat per ekor
            $table->integer('umur');
            $table->string('status');              // active, sold, dead, culled

            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('kelompok_ternak_id')->references('id')->on('ternaks');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('ternak_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelompok_ternak_id');
            $table->uuid('current_ternak_id');    // Reference ke current state
            $table->string('movement_type');      // purchase, sale, death, culled, transfer
            $table->dateTime('tanggal'); //tanggal
            $table->integer('quantity');
            $table->decimal('berat_total', 10, 2);
            $table->decimal('avg_berat', 10, 2);

            // Untuk transfer/mutasi
            $table->uuid('source_farm_id')->nullable();
            $table->uuid('source_coop_id')->nullable();
            $table->uuid('destination_farm_id')->nullable();
            $table->uuid('destination_coop_id')->nullable();

            // Untuk penjualan
            $table->uuid('pembeli_id')->nullable();
            $table->decimal('harga_jual', 10, 2)->nullable();
            $table->decimal('total_harga', 10, 2)->nullable();

            $table->string('reference_type')->nullable();  // Sale, Death, Transfer, etc
            $table->string('reference_id')->nullable();    // ID dari tabel terkait
            $table->string('keterangan')->nullable();
            $table->string('status');                     // draft, completed, cancelled

            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('source_farm_id')->references('id')->on('farms');
            $table->foreign('destination_farm_id')->references('id')->on('farms');
            $table->foreign('source_coop_id')->references('id')->on('coops');
            $table->foreign('destination_coop_id')->references('id')->on('coops');
            $table->foreign('kelompok_ternak_id')->references('id')->on('ternaks');
            $table->foreign('pembeli_id')->references('id')->on('partners');
            $table->foreign('current_ternak_id')->references('id')->on('current_ternaks');
        });

        Schema::create('mutasi_ternak', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelompok_ternak_asal_id');
            $table->uuid('kelompok_ternak_tujuan_id');
            $table->dateTime('tanggal_mutasi');
            $table->integer('jumlah_ternak');
            $table->decimal('berat_total', 10, 2);
            $table->uuid('farm_asal_id');
            $table->uuid('farm_tujuan_id');
            $table->uuid('kandang_asal_id');
            $table->uuid('kandang_tujuan_id');
            $table->string('alasan_mutasi');
            $table->string('keterangan')->nullable();
            $table->integer('umur');
            $table->string('status');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('kelompok_ternak_asal_id')->references('id')->on('ternaks');
            $table->foreign('kelompok_ternak_tujuan_id')->references('id')->on('ternaks');
            $table->foreign('farm_asal_id')->references('id')->on('farms');
            $table->foreign('farm_tujuan_id')->references('id')->on('farms');
            $table->foreign('kandang_asal_id')->references('id')->on('coops');
            $table->foreign('kandang_tujuan_id')->references('id')->on('coops');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('konsumsi_pakan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelompok_ternak_id');
            $table->uuid('item_id')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('harga', 10, 2);
            $table->dateTime('tanggal');
            $table->string('keterangan')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('kelompok_ternak_id')->references('id')->on('ternaks');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('ternak_mati', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelompok_ternak_id');
            $table->uuid('transaksi_id');
            $table->string('tipe_transaksi');
            $table->dateTime('tanggal');
            $table->uuid('farm_id');
            $table->uuid('coop_id');
            $table->string('stok_awal');
            $table->integer('quantity');
            $table->string('stok_akhir');
            $table->decimal('total_berat', 10, 2);
            $table->string('penyebab');
            $table->integer('umur');
            $table->string('keterangan')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->foreign('kelompok_ternak_id')->references('id')->on('ternaks');
        });

        Schema::create('ternak_jual', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelompok_ternak_id');
            $table->foreign('kelompok_ternak_id')->references('id')->on('ternaks');
            $table->uuid('transaksi_id');
            $table->string('tipe_transaksi');
            $table->dateTime('tanggal');
            $table->integer('quantity');
            // $table->decimal('harga', 10, 2); //harga satuan
            $table->decimal('total_berat', 10, 2)->default(0);
            $table->integer('umur');
            // $table->decimal('harga_jual', 10, 2); //harga jual
            // $table->decimal('total_harga', 10, 2);
            // $table->uuid('pembeli_id');
            // $table->foreign('pembeli_id')->references('id')->on('partners');
            // $table->string('jenis_penjualan'); // normal/afkir
            $table->string('keterangan')->nullable();
            $table->string('status');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('ternak_afkir', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelompok_ternak_id');
            $table->uuid('transaksi_id');
            $table->string('tipe_transaksi');
            $table->dateTime('tanggal');
            $table->integer('jumlah');
            $table->decimal('total_berat', 10, 2)->default(0);
            $table->string('kondisi')->nullable();
            $table->string('tindakan'); // dijual/dimusnahkan
            $table->integer('umur');
            $table->string('status');
            $table->string('keterangan')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('kelompok_ternak_id')->references('id')->on('ternaks');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('ternak_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelompok_ternak_id');
            $table->dateTime('tanggal');
            $table->string('stok_awal')->nullable();
            $table->string('stok_akhir')->nullable();
            $table->string('ternak_afkir')->nullable();
            $table->string('ternak_mati')->nullable();
            $table->string('ternak_jual')->nullable();
            $table->integer('umur');
            $table->string('status');
            $table->string('keterangan')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('kelompok_ternak_id')->references('id')->on('ternaks');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('afkir_ternak');
        Schema::dropIfExists('penjualan_ternak');
        Schema::dropIfExists('kematian_ternak');
        Schema::dropIfExists('konsumsi_pakan');
        Schema::dropIfExists('mutasi_ternak');
        Schema::dropIfExists('histori_ternak');
        Schema::dropIfExists('ternaks');
        Schema::dropIfExists('kelompok_ternak');

        Schema::enableForeignKeyConstraints();
    }
};
