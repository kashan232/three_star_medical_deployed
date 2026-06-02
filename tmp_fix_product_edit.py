from pathlib import Path
p = Path('resources/views/admin_panel/product/edit.blade.php')
text = p.read_text(encoding='utf-8')
text = text.replace('data-bs-toggle="modal"', 'data-toggle="modal"')
text = text.replace('data-bs-target="#categoryModal"', 'data-target="#categoryModal"')
text = text.replace('data-bs-target="#subcategoryModal"', 'data-target="#subcategoryModal"')
text = text.replace('data-bs-target="#brandModal"', 'data-target="#brandModal"')
old = '''                                <div class="col-md-4">
                                    <label class="form-label-pro">Category <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select class="form-select form-control-pro form-select-pro" id="category-dropdown" name="category_id" required>
                                            <option value="">Select...</option>
                                            @foreach ($categories as $cat) 
                                                <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option> 
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-light border ml-2" data-toggle="modal" data-target="#categoryModal"><i class="las la-plus"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-pro">Sub Category</label>
                                    <div class="input-group">
                                        <select class="form-select form-control-pro form-select-pro" id="subcategory-dropdown" name="sub_category_id">
                                            <option value="">Select...</option>
                                            @foreach($subcategories as $sub)
                                                @if($sub->category_id == $product->category_id)
                                                    <option value="{{ $sub->id }}" {{ $product->sub_category_id == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-light border ml-2" data-toggle="modal" data-target="#subcategoryModal"><i class="las la-plus"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-pro">Brand <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select class="form-select form-control-pro form-select-pro" name="brand_id" required>
                                            <option value="">Select...</option>
                                            @foreach ($brands as $brand) 
                                                <option value="{{ $brand->id }}" {{ $product->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option> 
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-light border ml-2" data-toggle="modal" data-target="#brandModal"><i class="las la-plus"></i></button>
                                    </div>
                                </div>
'''
new = '''                                <div class="col-md-4">
                                    <label class="form-label-pro">Category <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-end gap-2">
                                        <select class="form-select form-control-pro form-select-pro flex-grow-1" id="category-dropdown" name="category_id" required>
                                            <option value="">Select...</option>
                                            @foreach ($categories as $cat) 
                                                <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option> 
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-light border" data-toggle="modal" data-target="#categoryModal"><i class="las la-plus"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-pro">Sub Category</label>
                                    <div class="d-flex align-items-end gap-2">
                                        <select class="form-select form-control-pro form-select-pro flex-grow-1" id="subcategory-dropdown" name="sub_category_id">
                                            <option value="">Select...</option>
                                            @foreach($subcategories as $sub)
                                                @if($sub->category_id == $product->category_id)
                                                    <option value="{{ $sub->id }}" {{ $product->sub_category_id == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-light border" data-toggle="modal" data-target="#subcategoryModal"><i class="las la-plus"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-pro">Brand <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-end gap-2">
                                        <select class="form-select form-control-pro form-select-pro flex-grow-1" name="brand_id" required>
                                            <option value="">Select...</option>
                                            @foreach ($brands as $brand) 
                                                <option value="{{ $brand->id }}" {{ $product->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option> 
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-light border" data-toggle="modal" data-target="#brandModal"><i class="las la-plus"></i></button>
                                    </div>
                                </div>
'''
if old not in text:
    raise ValueError('Old block not found')
text = text.replace(old, new)
insert_after = '                        <button type="submit" class="btn btn-primary w-100 rounded-pill">Create Subcategory</button>\n                        </div>\n                    </form>\n                </div>\n            </div>\n        </div>\n\n'
brand = '''        <div id="brandModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-md);">
                    <form action="{{ route('store.Brand') }}" method="POST">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h6 class="modal-title fw-bold">New Brand</h6>
                            <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="page" value="product_page">
                            <div class="mb-3">
                                <label class="form-label-pro">Brand Name</label>
                                <input type="text" name="name" class="form-control-pro" required placeholder="e.g. Acme Corp">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill">Create Brand</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>\n'''
if insert_after not in text:
    raise ValueError('Insert location not found')
text = text.replace(insert_after, insert_after + brand)
p.write_text(text, encoding='utf-8')
print('ok')
