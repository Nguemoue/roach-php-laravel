<?php

/**
 * Exemple d'ItemProcessor pour sauvegarder les données en base de données
 * 
 * Ce processeur prend les items scrapés et les sauvegarde dans une table MySQL.
 * 
 * Pour l'utiliser :
 * 1. Créez une migration pour votre table
 * 2. Créez un modèle Eloquent
 * 3. Copiez ce fichier dans app/ItemProcessors/
 * 4. Ajoutez-le à la propriété $itemProcessors de votre Spider
 */

namespace App\ItemProcessors;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Support\Configurable;

class DatabaseItemProcessor implements ItemProcessorInterface
{
    use Configurable;

    /**
     * Nom de la table où sauvegarder les données
     */
    private string $table = 'scraped_items';

    /**
     * Champs à utiliser pour éviter les doublons
     */
    private array $uniqueFields = ['url'];

    /**
     * Configure le processeur
     */
    public function configure(array $options): void
    {
        $this->table = $options['table'] ?? $this->table;
        $this->uniqueFields = $options['unique_fields'] ?? $this->uniqueFields;
    }

    /**
     * Traite et sauvegarde l'item
     */
    public function processItem(ItemInterface $item): ItemInterface
    {
        try {
            // Préparer les données
            $data = $item->all();
            $data['created_at'] = now();
            $data['updated_at'] = now();

            // Construire la condition WHERE pour les champs uniques
            $whereConditions = [];
            foreach ($this->uniqueFields as $field) {
                if (isset($data[$field])) {
                    $whereConditions[$field] = $data[$field];
                }
            }

            // Utiliser updateOrInsert pour éviter les doublons
            if (!empty($whereConditions)) {
                DB::table($this->table)->updateOrInsert(
                    $whereConditions,
                    $data
                );
            } else {
                DB::table($this->table)->insert($data);
            }

            Log::info('Item sauvegardé en base de données', [
                'table' => $this->table,
                'url' => $data['url'] ?? 'N/A',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la sauvegarde en base de données', [
                'error' => $e->getMessage(),
                'item' => $item->all(),
            ]);
        }

        return $item;
    }
}
