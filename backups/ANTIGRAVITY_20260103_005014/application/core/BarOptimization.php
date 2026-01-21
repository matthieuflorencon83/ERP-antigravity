<?php
class BarOptimization {
    
    /**
     * Finds the most economical standard bar for a given need.
     * Strategy: Cheapest First (Shortest valid length)
     * 
     * @param int $neededLength in mm
     * @param array $availableLengths Array of integers [4700, 6000, 6500] in mm
     * @return array Result with status, selected bar, waste amount and percentage
     */
    public function optimize(int $neededLength, array $availableLengths): array {
        // 1. Filter out bars that are too short
        $candidates = array_filter($availableLengths, fn($l) => $l >= $neededLength);
        
        // Edge case: No bar is long enough
        if (empty($candidates)) {
            return [
                'status' => 'ERROR', 
                'msg' => "Aucune barre standard n'est assez longue ({$neededLength}mm requis).",
                'recommended_bar' => null,
                'waste_mm' => 0,
                'waste_percent' => 0
            ];
        }
        
        // 2. Sort available lengths ascending (Cheapest/Shortest first)
        sort($candidates);
        $bestBar = $candidates[0]; // The smallest one that fits
        
        // 3. Calculate waste
        $waste = $bestBar - $neededLength;
        $wastePercent = ($bestBar > 0) ? ($waste / $bestBar) * 100 : 0;
        
        return [
            'status' => 'OK',
            'recommended_bar' => $bestBar,
            'waste_mm' => $waste,
            'waste_percent' => round($wastePercent, 2)
        ];
    }
    
    /**
     * Batch Optimization (Bin Packing 1D) - Placeholder for V2.1
     * To be implemented when we want to optimize a whole list of cuts within stock bars.
     */
    public function optimizeBatch(array $cuts, array $stockLengths) {
        // FUTURE: Implement Best Fit Decreasing algorithm
        return ['status' => 'NOT_IMPLEMENTED'];
    }
}
