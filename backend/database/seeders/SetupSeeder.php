<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SetupSeeder extends Seeder
{
    public function run(): void
    {
        // Este seeder solo se ejecuta cuando no exista este usuario
        $admin = User::query()->where('email', 'admin@farmart.com')->first();

        // Evita duplicados si el seeder se ejecuta varias veces.
        if ($admin !== null) {
            return;
        }

        // Catálogo de roles con los ids fijos del esquema.
        $roles = [
            1 => 'auxiliar_farmacia',
            2 => 'regente_farmacia',
            3 => 'medico',
            4 => 'auditor',
            5 => 'admin',
        ];

        foreach ($roles as $id => $name) {
            // Equivale a ON CONFLICT DO NOTHING: omite el rol si el id o el nombre ya existen.
            $exists = Role::query()
                ->where('id', $id)
                ->orWhere('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            Role::factory()->named($name)->create(['id' => $id]);
        }

        // Usuarios del sistema con los ids fijos del esquema.
        $users = [
            1 => ['Auxiliar Farmacia', 'auxiliar@farmart.com', 1],
            2 => ['Regente Farmacia', 'regente@farmart.com', 2],
            3 => ['Dra. Laura Médica', 'medico@farmart.com', 3],
            4 => ['Auditor', 'auditor@farmart.com', 4],
            5 => ['Administrador', 'admin@farmart.com', 5],
        ];

        foreach ($users as $id => [$name, $email, $roleId]) {
            // Equivale a ON CONFLICT DO NOTHING: omite el usuario si el id o el email ya existen.
            $exists = User::query()
                ->where('id', $id)
                ->orWhere('email', $email)
                ->exists();

            if ($exists) {
                continue;
            }

            User::factory()->create([
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Hash::make('admin0615'),
                'role_id' => $roleId,
            ]);
        }

        // Catálogo de productos con los ids fijos del esquema.
        $products = [
            1 => ['Acetaminofén 500 mg', false],
            2 => ['Ibuprofeno 400 mg', false],
            3 => ['Amoxicilina 500 mg', false],
            4 => ['Loratadina 10 mg', false],
            5 => ['Metformina 850 mg', false],
            6 => ['Morfina 10 mg', true],
        ];

        foreach ($products as $id => [$name, $isSpecial]) {
            // Equivale a ON CONFLICT DO NOTHING: omite el producto si el id o el nombre ya existen.
            $exists = Product::query()
                ->where('id', $id)
                ->orWhere('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            Product::factory()->create([
                'id' => $id,
                'name' => $name,
                'is_special' => $isSpecial,
            ]);
        }

        // Catálogo de lotes con los ids fijos del esquema.
        $batches = [
            1 => [1, 'L-ACE-001', now()->subDays(10)],  // vencido
            2 => [1, 'L-ACE-002', now()->addDays(20)],  // vence en < 30 días
            3 => [1, 'L-ACE-003', now()->addDays(200)],
            4 => [2, 'L-IBU-001', now()->addDays(15)],  // vence en < 30 días
            5 => [2, 'L-IBU-002', now()->addDays(180)],
            6 => [3, 'L-AMO-001', now()->addDays(60)],
            7 => [3, 'L-AMO-002', now()->addDays(300)],
            8 => [4, 'L-LOR-001', now()->subDays(5)],   // vencido
            9 => [4, 'L-LOR-002', now()->addDays(150)],
            10 => [5, 'L-MET-001', now()->addDays(90)],
            11 => [5, 'L-MET-002', now()->addDays(365)],
            12 => [6, 'L-MOR-001', now()->addDays(45)],
            13 => [6, 'L-MOR-002', now()->addDays(270)],
        ];

        foreach ($batches as $id => [$productId, $code, $expiresOn]) {
            // Equivale a ON CONFLICT DO NOTHING: valida por id y por (product_id, code).
            $exists = Batch::query()
                ->where('id', $id)
                ->orWhere(function ($query) use ($productId, $code) {
                    $query->where('product_id', $productId)->where('code', $code);
                })
                ->exists();

            if ($exists) {
                continue;
            }

            Batch::factory()->create([
                'id' => $id,
                'product_id' => $productId,
                'code' => $code,
                'expires_on' => $expiresOn->toDateString(),
            ]);
        }

        // Catálogo de bodegas con los ids fijos del esquema.
        $warehouses = [
            1 => 'Farmacia Central',
            2 => 'Farmacia Urgencias',
            3 => 'Bodega Hospitalización',
        ];

        foreach ($warehouses as $id => $name) {
            // Equivale a ON CONFLICT DO NOTHING: omite la bodega si el id o el nombre ya existen.
            $exists = Warehouse::query()
                ->where('id', $id)
                ->orWhere('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            Warehouse::factory()->create([
                'id' => $id,
                'name' => $name,
            ]);
        }

        // Catálogo de pacientes sintéticos con los ids fijos del esquema.
        $patients = [
            1 => ['CC-900100200', 'Ana María Gómez'],
            2 => ['CC-900100300', 'Carlos Andrés Ruiz'],
            3 => ['CC-900100400', 'Luisa Fernanda Torres'],
        ];

        foreach ($patients as $id => [$document, $fullName]) {
            // Equivale a ON CONFLICT DO NOTHING: omite el paciente si el id o el documento ya existen.
            $exists = Patient::query()
                ->where('id', $id)
                ->orWhere('document', $document)
                ->exists();

            if ($exists) {
                continue;
            }

            Patient::factory()->create([
                'id' => $id,
                'document' => $document,
                'full_name' => $fullName,
            ]);
        }

        // Prescripciones vigentes con los ids fijos del esquema.
        $prescriptions = [
            1 => [1, 3, now()->addDays(30)],
            2 => [2, 3, now()->addDays(45)],
            3 => [3, 3, now()->addDays(60)], // incluye medicamento de control especial
        ];

        foreach ($prescriptions as $id => [$patientId, $doctorId, $validUntil]) {
            // Equivale a ON CONFLICT DO NOTHING: omite la prescripción si el id ya existe.
            $exists = Prescription::query()->where('id', $id)->exists();

            if ($exists) {
                continue;
            }

            Prescription::factory()->create([
                'id' => $id,
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'valid_until' => $validUntil->toDateString(),
            ]);
        }

        // Ítems de prescripción con los ids fijos del esquema.
        $prescriptionItems = [
            1 => [1, 1, 20],
            2 => [1, 2, 10],
            3 => [2, 3, 15],
            4 => [2, 4, 10],
            5 => [3, 6, 5],  // Morfina (control especial)
            6 => [3, 5, 30],
        ];

        foreach ($prescriptionItems as $id => [$prescriptionId, $productId, $quantity]) {
            // Equivale a ON CONFLICT DO NOTHING: valida por id y por (prescription_id, product_id).
            $exists = PrescriptionItem::query()
                ->where('id', $id)
                ->orWhere(function ($query) use ($prescriptionId, $productId) {
                    $query->where('prescription_id', $prescriptionId)
                        ->where('product_id', $productId);
                })
                ->exists();

            if ($exists) {
                continue;
            }

            PrescriptionItem::factory()->create([
                'id' => $id,
                'prescription_id' => $prescriptionId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }

        $this->call(SetupStockSeeder::class);
    }
}
