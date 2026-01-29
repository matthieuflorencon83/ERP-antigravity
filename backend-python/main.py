from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Optional
from optimizer import BarOptimizer

app = FastAPI(title="ERP Arts Alu - Calculation Engine")

# Data Models (Rule #5: Type Hinting)
class StockOption(BaseModel):
    ref: str
    len_mm: int

class OptimizationRequest(BaseModel):
    stock_options: List[StockOption]
    cuts_mm: List[int]
    saw_kerf: int = 4
    scrap_end: int = 0

class Bin(BaseModel):
    ref: str
    len_mm: int
    free: int
    cuts: List[int]

class OptimizationResponse(BaseModel):
    bins: List[Bin]
    oversized_cuts: List[int]

@app.get("/health")
def health_check():
    return {"status": "ok", "service": "python-calculation-engine"}

@app.post("/optimize", response_model=OptimizationResponse)
def optimize(request: OptimizationRequest):
    """
    Endpoint for 1D Bin Packing Optimization.
    Delegates logic to the ported optimizer.py.
    """
    try:
        # Convert Pydantic models to dicts for the legacy optimizer if needed, 
        # or adapt the optimizer to accept objects.
        # simpler to just pass dicts as the original code expects dicts for stock_options.
        
        stock_dicts = [s.dict() for s in request.stock_options]
        
        bins, oversized = BarOptimizer.solve_multi(
            stock_dicts, 
            request.cuts_mm, 
            request.saw_kerf, 
            request.scrap_end
        )
        
        return {
            "bins": bins,
            "oversized_cuts": oversized
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(s))

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
