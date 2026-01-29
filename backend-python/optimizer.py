class BarOptimizer:
    """ 1D Bin Packing with Multiple Stock Lengths """
    @staticmethod
    def solve_multi(stock_options, cuts_mm, saw_kerf=4, scrap_end=0):
        """
        Returns: (bins, oversized_cuts)
        scrap_end: Unusable length at the end of every bar (mm).
        stock_options: list of dict {'ref', 'len_mm'}
        """
        # Sort stock options by length descending (Try to fit in big first)
        stocks = sorted(stock_options, key=lambda x: x['len_mm'], reverse=True)
        if not stocks: return ([], cuts_mm)
        
        # LOGIC ADJUSTMENT:
        # Effective deduction from available space = max(0, scrap_end - saw_kerf).
        effective_deduction = max(0, scrap_end - saw_kerf)
        
        # Longest usable length
        longest_bar_len = stocks[0]['len_mm'] - effective_deduction
        
        # Filter Oversized
        valid_cuts = []
        oversized_cuts = []
        for c in cuts_mm:
            if c > longest_bar_len:
                oversized_cuts.append(c)
            else:
                valid_cuts.append(c)
                
        # Sort valid cuts descending
        cuts = sorted(valid_cuts, reverse=True)
        
        # Phase 1: First Fit Decreasing into Longest Bars
        bins = []
        
        # Reference longest bar properties
        long_ref = stocks[0]['ref']
        long_len = stocks[0]['len_mm']
        long_usable = long_len - effective_deduction
        
        for cut in cuts:
            needed = cut + saw_kerf
            placed = False
            
            # Try to fit in existing open bins
            for b in bins:
                if b['free'] >= needed:
                    b['free'] -= needed
                    b['cuts'].append(cut)
                    placed = True
                    break
            
            if not placed:
                new_bin = {
                    'ref': long_ref,
                    'len_mm': long_len,
                    'free': long_usable - needed, 
                    'cuts': [cut]
                }
                bins.append(new_bin)
        
        # Phase 2: Downgrade Bins (Optimize Mix)
        final_bins = []
        for b in bins:
            # Re-calculate what is strictly needed (content)
            total_needed = sum(c + saw_kerf for c in b['cuts'])
            
            # Find best fit stock
            best_stock = None
            candidates = [s for s in stock_options if (s['len_mm'] - effective_deduction) >= total_needed]
            if candidates:
                best_stock = min(candidates, key=lambda x: x['len_mm'])
            
            if best_stock:
                final_bins.append({
                    'ref': best_stock['ref'],
                    'len_mm': best_stock['len_mm'],
                    'free': (best_stock['len_mm'] - effective_deduction) - total_needed, 
                    'cuts': b['cuts']
                })
            else:
                final_bins.append(b)
                
        return (final_bins, oversized_cuts)
