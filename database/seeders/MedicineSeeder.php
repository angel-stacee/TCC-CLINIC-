<?php

namespace Database\Seeders;

use App\Models\ClinicVisit;
use App\Models\Medicine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Remove redundant exact duplicate rows that are NOT referenced in clinic visits
        $referencedIds = ClinicVisit::whereNotNull('medicine_id')
            ->pluck('medicine_id')
            ->unique()
            ->toArray();

        // Safe cleanup of unreferenced exact duplicates
        $duplicateGroups = Medicine::select('medicine_name', DB::raw('MIN(id) as keep_id'))
            ->groupBy('medicine_name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            Medicine::where('medicine_name', $group->medicine_name)
                ->where('id', '!=', $group->keep_id)
                ->whereNotIn('id', $referencedIds)
                ->delete();
        }

        // 2. Comprehensive catalog of clinic medications, supplies, and compression equipment
        $items = [
            // --- First Aid ---
            [
                'medicine_name' => 'Adhesive Bandage',
                'treatment_type' => 'First Aid',
                'unit' => 'Piece',
                'stock' => 200,
                'minimum_stock' => 30,
                'description' => 'Sterile adhesive strips for minor cuts, scratches, and blisters.',
            ],
            [
                'medicine_name' => 'Gauze Pads (Sterile 4x4)',
                'treatment_type' => 'First Aid',
                'unit' => 'Piece',
                'stock' => 150,
                'minimum_stock' => 25,
                'description' => 'Sterile absorbent cotton gauze pads for wound dressing and cleansing.',
            ],
            [
                'medicine_name' => 'Medical Tape',
                'treatment_type' => 'First Aid',
                'unit' => 'Roll',
                'stock' => 100,
                'minimum_stock' => 15,
                'description' => 'Hypoallergenic adhesive medical surgical tape for securing dressings.',
            ],
            [
                'medicine_name' => 'Cotton Balls',
                'treatment_type' => 'First Aid',
                'unit' => 'Pack',
                'stock' => 120,
                'minimum_stock' => 20,
                'description' => 'Sterile absorbent cotton balls for cleaning skin and applying solutions.',
            ],
            [
                'medicine_name' => 'Alcohol Pads (70% Isopropyl)',
                'treatment_type' => 'First Aid',
                'unit' => 'Piece',
                'stock' => 250,
                'minimum_stock' => 50,
                'description' => 'Individual antiseptic 70% isopropyl alcohol prep swabs.',
            ],
            [
                'medicine_name' => 'Disposable Nitrile Gloves',
                'treatment_type' => 'First Aid',
                'unit' => 'Pair',
                'stock' => 150,
                'minimum_stock' => 30,
                'description' => 'Powder-free medical examination gloves for safe clinical procedures.',
            ],
            [
                'medicine_name' => 'Elastic Bandage (Ace/Crepe 3")',
                'treatment_type' => 'First Aid',
                'unit' => 'Roll',
                'stock' => 100,
                'minimum_stock' => 20,
                'description' => 'Elastic compression bandage for joint stabilization, sprains, and support.',
            ],
            [
                'medicine_name' => 'Triangular Bandage',
                'treatment_type' => 'First Aid',
                'unit' => 'Piece',
                'stock' => 50,
                'minimum_stock' => 10,
                'description' => 'Multi-purpose triangular cloth bandage for arm slings and splint immobilization.',
            ],
            [
                'medicine_name' => 'Bandage Scissors',
                'treatment_type' => 'First Aid',
                'unit' => 'Piece',
                'stock' => 15,
                'minimum_stock' => 5,
                'description' => 'Blunt-tip medical trauma shears for cutting bandages and clothing safely.',
            ],
            [
                'medicine_name' => 'CPR Face Shield',
                'treatment_type' => 'First Aid',
                'unit' => 'Piece',
                'stock' => 25,
                'minimum_stock' => 5,
                'description' => 'One-way valve CPR resuscitation barrier shield for emergency response.',
            ],
            [
                'medicine_name' => 'Padded Aluminum Splint',
                'treatment_type' => 'First Aid',
                'unit' => 'Piece',
                'stock' => 20,
                'minimum_stock' => 5,
                'description' => 'Malleable foam-padded aluminum splint for bone and joint immobilization.',
            ],
            [
                'medicine_name' => 'Oral Glucose Gel',
                'treatment_type' => 'First Aid',
                'unit' => 'Pack',
                'stock' => 50,
                'minimum_stock' => 10,
                'description' => 'Fast-acting oral glucose gel for acute hypoglycemic episodes.',
            ],
            [
                'medicine_name' => 'Burn Dressing (Sterile Sheet)',
                'treatment_type' => 'First Aid',
                'unit' => 'Pack',
                'stock' => 40,
                'minimum_stock' => 10,
                'description' => 'Non-adherent cooling sterile burn dressing sheet for thermal injuries.',
            ],

            // --- Hot Compress ---
            [
                'medicine_name' => 'Hot Water Bag',
                'treatment_type' => 'Hot Compress',
                'unit' => 'Piece',
                'stock' => 30,
                'minimum_stock' => 5,
                'description' => 'Rubber hot water bottle with ribbed design for muscle stiffness and pain.',
            ],
            [
                'medicine_name' => 'Reusable Hot Pack',
                'treatment_type' => 'Hot Compress',
                'unit' => 'Piece',
                'stock' => 50,
                'minimum_stock' => 10,
                'description' => 'Microwaveable reusable thermal gel pack for heat therapy.',
            ],
            [
                'medicine_name' => 'Warm Compress Pack',
                'treatment_type' => 'Hot Compress',
                'unit' => 'Piece',
                'stock' => 60,
                'minimum_stock' => 10,
                'description' => 'Instant air-activated warm compress for soothing abdominal and joint pain.',
            ],
            [
                'medicine_name' => 'Menstrual Cramp Heat Patch',
                'treatment_type' => 'Hot Compress',
                'unit' => 'Piece',
                'stock' => 100,
                'minimum_stock' => 20,
                'description' => 'Adhesive soothing thermal warming patch for dysmenorrhea and abdominal cramps.',
            ],
            [
                'medicine_name' => 'Moist Heat Therapy Towel Wrap',
                'treatment_type' => 'Hot Compress',
                'unit' => 'Piece',
                'stock' => 40,
                'minimum_stock' => 8,
                'description' => 'Deep-penetrating moist heat compress wrap for neck, back, and muscular tension.',
            ],

            // --- Cold Compress ---
            [
                'medicine_name' => 'Instant Cold Pack',
                'treatment_type' => 'Cold Compress',
                'unit' => 'Piece',
                'stock' => 80,
                'minimum_stock' => 15,
                'description' => 'Instant chemical activation squeeze pack for acute sprains, bruises, and swelling.',
            ],
            [
                'medicine_name' => 'Reusable Cold Pack',
                'treatment_type' => 'Cold Compress',
                'unit' => 'Piece',
                'stock' => 60,
                'minimum_stock' => 10,
                'description' => 'Flexible freezing gel ice pack for acute trauma and swelling reduction.',
            ],
            [
                'medicine_name' => 'Ice Bag with Cap',
                'treatment_type' => 'Cold Compress',
                'unit' => 'Piece',
                'stock' => 30,
                'minimum_stock' => 5,
                'description' => 'Waterproof fabric ice collar/bag with leak-proof cap for cryotherapy.',
            ],
            [
                'medicine_name' => 'Cooling Gel Fever Patch',
                'treatment_type' => 'Cold Compress',
                'unit' => 'Pack',
                'stock' => 120,
                'minimum_stock' => 25,
                'description' => 'Hydrogel forehead cooling relief patch for high fever and heat exhaustion.',
            ],
            [
                'medicine_name' => 'Cold Compression Wrap with Strap',
                'treatment_type' => 'Cold Compress',
                'unit' => 'Piece',
                'stock' => 35,
                'minimum_stock' => 5,
                'description' => 'Cold therapy wrap with Velcro straps to secure cold pack to joints.',
            ],
            [
                'medicine_name' => 'Instant Freeze Spray',
                'treatment_type' => 'Cold Compress',
                'unit' => 'Can',
                'stock' => 20,
                'minimum_stock' => 5,
                'description' => 'Rapid topical cooling spray for sports contusions and muscle sprains.',
            ],

            // --- Wound Care ---
            [
                'medicine_name' => 'Povidone-Iodine 10% Solution (Betadine)',
                'treatment_type' => 'Wound Care',
                'unit' => 'Bottle',
                'stock' => 50,
                'minimum_stock' => 10,
                'description' => 'Broad-spectrum antiseptic solution for skin disinfection and wound irrigation.',
            ],
            [
                'medicine_name' => 'Povidone-Iodine Swabsticks',
                'treatment_type' => 'Wound Care',
                'unit' => 'Pack',
                'stock' => 80,
                'minimum_stock' => 15,
                'description' => 'Single-use pre-saturated antiseptic swabsticks for clean wound application.',
            ],
            [
                'medicine_name' => 'Antiseptic Solution',
                'treatment_type' => 'Wound Care',
                'unit' => 'Bottle',
                'stock' => 50,
                'minimum_stock' => 10,
                'description' => 'Gentle antiseptic cleanser for shallow cuts, abrasions, and skin lesions.',
            ],
            [
                'medicine_name' => 'Normal Saline (0.9% NaCl Irrigation)',
                'treatment_type' => 'Wound Care',
                'unit' => 'Bottle',
                'stock' => 60,
                'minimum_stock' => 10,
                'description' => 'Sterile isotonic sodium chloride solution for wound rinsing and debridement.',
            ],
            [
                'medicine_name' => 'Hydrogen Peroxide 3%',
                'treatment_type' => 'Wound Care',
                'unit' => 'Bottle',
                'stock' => 40,
                'minimum_stock' => 8,
                'description' => 'Antiseptic cleanser with foaming action for removing debris from contaminated wounds.',
            ],
            [
                'medicine_name' => 'Topical Antibiotic Ointment',
                'treatment_type' => 'Wound Care',
                'unit' => 'Tube',
                'stock' => 50,
                'minimum_stock' => 10,
                'description' => 'Broad-spectrum triple antibiotic ointment preventing infection in minor cuts.',
            ],
            [
                'medicine_name' => 'Hydrocortisone 1% Cream',
                'treatment_type' => 'Wound Care',
                'unit' => 'Tube',
                'stock' => 40,
                'minimum_stock' => 8,
                'description' => 'Topical corticosteroid for itching, allergic skin reactions, and insect bites.',
            ],
            [
                'medicine_name' => 'Burn Gel',
                'treatment_type' => 'Wound Care',
                'unit' => 'Tube',
                'stock' => 60,
                'minimum_stock' => 10,
                'description' => 'Cooling antiseptic gel formulated for minor burns, scalds, and sunburns.',
            ],
            [
                'medicine_name' => 'Calamine Lotion',
                'treatment_type' => 'Wound Care',
                'unit' => 'Bottle',
                'stock' => 35,
                'minimum_stock' => 8,
                'description' => 'Soothing anti-pruritic topical suspension for rashes, hives, and chickenpox itch.',
            ],
            [
                'medicine_name' => 'Conforming Gauze Roll (3" x 4.1 yds)',
                'treatment_type' => 'Wound Care',
                'unit' => 'Roll',
                'stock' => 80,
                'minimum_stock' => 15,
                'description' => 'Breathable stretch gauze roll for wrapping wounds and securing pads.',
            ],
            [
                'medicine_name' => 'Non-Adherent Dressing (Telfa)',
                'treatment_type' => 'Wound Care',
                'unit' => 'Piece',
                'stock' => 100,
                'minimum_stock' => 20,
                'description' => 'Specialized non-stick wound dressing pad that does not adhere to healing tissue.',
            ],
            [
                'medicine_name' => 'Butterfly Wound Closure Strips',
                'treatment_type' => 'Wound Care',
                'unit' => 'Pack',
                'stock' => 50,
                'minimum_stock' => 10,
                'description' => 'Sterile butterfly closure strips for holding wound edges together without stitches.',
            ],
            [
                'medicine_name' => 'Silver Sulfadiazine 1% Cream',
                'treatment_type' => 'Wound Care',
                'unit' => 'Tube',
                'stock' => 25,
                'minimum_stock' => 5,
                'description' => 'Topical antimicrobial agent for preventing bacterial infection in 2nd-degree burns.',
            ],

            // --- Medication ---
            [
                'medicine_name' => 'Paracetamol 500mg',
                'treatment_type' => 'Medication',
                'unit' => 'Tablet',
                'stock' => 300,
                'minimum_stock' => 50,
                'description' => 'First-line antipyretic and analgesic for headaches, body aches, and fever.',
            ],
            [
                'medicine_name' => 'Ibuprofen 200mg',
                'treatment_type' => 'Medication',
                'unit' => 'Tablet',
                'stock' => 150,
                'minimum_stock' => 30,
                'description' => 'Non-steroidal anti-inflammatory drug for pain, swelling, and dysmenorrhea.',
            ],
            [
                'medicine_name' => 'Mefenamic Acid 500mg',
                'treatment_type' => 'Medication',
                'unit' => 'Tablet',
                'stock' => 150,
                'minimum_stock' => 30,
                'description' => 'Effective NSAID for acute dental pain, headache, and severe menstrual cramps.',
            ],
            [
                'medicine_name' => 'Neozep (Phenylephrine + Paracetamol)',
                'treatment_type' => 'Medication',
                'unit' => 'Tablet',
                'stock' => 150,
                'minimum_stock' => 30,
                'description' => 'Multi-symptom decongestant and pain reliever for colds, sinusitis, and flu.',
            ],
            [
                'medicine_name' => 'Cetirizine 10mg',
                'treatment_type' => 'Medication',
                'unit' => 'Tablet',
                'stock' => 200,
                'minimum_stock' => 40,
                'description' => 'Second-generation antihistamine for allergic rhinitis, sneezing, and urticaria.',
            ],
            [
                'medicine_name' => 'Loratadine 10mg',
                'treatment_type' => 'Medication',
                'unit' => 'Tablet',
                'stock' => 120,
                'minimum_stock' => 25,
                'description' => 'Non-drowsy 24-hour antihistamine for seasonal allergy symptoms and hives.',
            ],
            [
                'medicine_name' => 'Antacid (Al(OH)3 + Mg(OH)2)',
                'treatment_type' => 'Medication',
                'unit' => 'Tablet',
                'stock' => 150,
                'minimum_stock' => 30,
                'description' => 'Chewable antacid for rapid neutralization of stomach acid and heartburn relief.',
            ],
            [
                'medicine_name' => 'Simethicone 40mg',
                'treatment_type' => 'Medication',
                'unit' => 'Tablet',
                'stock' => 100,
                'minimum_stock' => 20,
                'description' => 'Anti-foaming agent relieving abdominal bloating, pressure, and gas.',
            ],
            [
                'medicine_name' => 'Loperamide 2mg',
                'treatment_type' => 'Medication',
                'unit' => 'Tablet',
                'stock' => 120,
                'minimum_stock' => 25,
                'description' => 'Antidiarrheal medication for symptomatic relief of acute watery diarrhea.',
            ],
            [
                'medicine_name' => 'Oral Rehydration Salts (ORS)',
                'treatment_type' => 'Medication',
                'unit' => 'Sachet',
                'stock' => 150,
                'minimum_stock' => 30,
                'description' => 'Electrolyte replenishment powder packets for dehydration prevention.',
            ],
            [
                'medicine_name' => 'Hyoscine-N-butylbromide 10mg',
                'treatment_type' => 'Medication',
                'unit' => 'Tablet',
                'stock' => 100,
                'minimum_stock' => 20,
                'description' => 'Antispasmodic for relieving painful smooth muscle cramps in stomach and bowel.',
            ],
            [
                'medicine_name' => 'Ascorbic Acid (Vitamin C 500mg)',
                'treatment_type' => 'Medication',
                'unit' => 'Tablet',
                'stock' => 250,
                'minimum_stock' => 50,
                'description' => 'Daily immune defense supplement for students and faculty recovering from illness.',
            ],
            [
                'medicine_name' => 'Diphenhydramine 25mg',
                'treatment_type' => 'Medication',
                'unit' => 'Capsule',
                'stock' => 80,
                'minimum_stock' => 15,
                'description' => 'Fast-acting first-generation antihistamine for acute allergic reactions and hives.',
            ],
        ];

        foreach ($items as $item) {
            // Find existing medicine by name or similar key
            $existing = Medicine::where('medicine_name', $item['medicine_name'])->first();

            if (! $existing) {
                // Try fuzzy lookup for slight variations like "Paracetamol" vs "Paracetamol 500mg"
                $baseName = explode(' ', $item['medicine_name'])[0];
                $existing = Medicine::where('treatment_type', $item['treatment_type'])
                    ->where('medicine_name', 'like', $baseName.'%')
                    ->first();
            }

            if ($existing) {
                // Update to healthy stock if stock is low or minimum_stock is not set
                $newStock = max($existing->stock, $item['stock']);
                $existing->update([
                    'medicine_name' => $item['medicine_name'],
                    'treatment_type' => $item['treatment_type'],
                    'unit' => $item['unit'],
                    'stock' => $newStock,
                    'minimum_stock' => $item['minimum_stock'],
                    'description' => $existing->description ?: $item['description'],
                ]);
            } else {
                Medicine::create($item);
            }
        }
    }
}
