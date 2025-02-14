@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="container mt-4">
	<h2>Edit Category</h2>

	<!-- Form -->
	<form action="{{ route('categoryFilter.update', $category->id) }}" method="POST">
		@csrf
		@method('PUT')

		<div class="row">
			<div class="mb-3 col-md-12">
				<label for="category" class="form-label">Category</label>
				<input type="text" class="form-control" id="category" value="{{ $category->name }}" readonly>
			</div>
		</div>

		{{-- <div class="row">
			<div class="mb-3 col-md-12">
				<label for="product_types" class="form-label">Product Types</label>
				<select id="product_types" name="product_types[]" class="form-control select2" multiple>
					@foreach ($productTypes as $type)
						<option value="{{ $type->id }}" {{ in_array($type->id, $category->productTypes->pluck('id')->toArray()) ? 'selected' : '' }}>
							{{ $type->name }}
						</option>
					@endforeach
				</select>
			</div>
		</div> --}}

		<!-- Specifications -->
		<div id="specification-container">
			<div class="row">
				<div class="col-md-2">
					<label for="specifications">Specification Type</label>
				</div>
				<div class="col-md-3">
					<label for="specifications">Specification Name</label>
				</div>
				<div class="col-md-7">
					<label for="specifications">Specification Values</label>
				</div>
			</div>
			@foreach ($category->specifications as $index => $specification)
				<div class="specification-group mt-3">
					<div class="row">
						{{-- <div class="col-md-1 justify-content-end text-end"> --}}
						<div class="col-md-2">
							{{-- <input
								class="mt-2"
								type="checkbox"
								name="specifications[{{$index}}][is_checked]"
								value="1"
								{{ $specification->is_checked ? 'checked' : '' }}
							/> --}}
							@php
								$selectedSpecificationTypes = explode(",", $specification->specification_type);
							@endphp
							<select name="specifications[{{$index}}][specification_type][]" class="form-control select2 specTypes" multiple>
								@foreach ($specificationTypes as $type)
									<option value="{{ $type }}" {{ in_array($type, $selectedSpecificationTypes) ? 'selected' : '' }}>
										{{ $type }}
									</option>
								@endforeach
							</select>
						</div>

						<div class="col-md-3">
							<select name="specifications[{{$index}}][name]" class="form-control select2 specNames">
								@foreach ($specificationNames as $name)
									<option value="">Select</option>
									<option value="{{ $name }}" {{ $name==$specification->specification_name ? 'selected' : '' }}>
										{{ $name }}
									</option>
								@endforeach
							</select>
						</div>
						<div class="col-md-7">
							<div class="row specification-values" id="specification_value_{{$index}}">
								@php($specVals = $specification->specification_values ? explode("|", $specification->specification_values) : [])
								@foreach ($specVals as $index2 => $specVal)
									<div class="col-md-2 mb-2">
										<input
											type="text"
											name="specifications[{{$index}}][vals][]"
											class="form-control"
											value="{{ $specVal ?? '' }}"
											placeholder=""
										/>
									</div>
									@if($index2 == 4)
										<div class="col-md-2">
											<button
												type="button"
												class="btn btn-success add-specification-value"
												data-index="{{$index}}">
												<i class="fas fa-plus"></i>
											</button>
										</div>
									@endif
								@endforeach

								@for ($j = count($specVals); $j < 5; $j++)
									<div class="col-md-2 mb-2">
										<input
											type="text"
											name="specifications[{{$index}}][vals][]"
											class="form-control"
											value="{{ $specVals[$j] ?? '' }}"
											placeholder="Value {{ $j + 1 }}"
										/>
									</div>
								@endfor

								@if (count($specVals) < 5)
									<div class="col-md-2">
										<button
											type="button"
											class="btn btn-success add-specification-value"
											data-index="{{$index}}">
											<i class="fas fa-plus"></i>
										</button>
									</div>
								@endif
							</div>
						</div>
					</div>
					@if ($index >= 3)
						<div class="row mt-1">
							<div class="col-md-12 text-end">
								<button
									type="button"
									class="btn btn-danger remove-specification"
									data-index="{{ $index }}">
									Remove
								</button>
							</div>
						</div>
					@endif
				</div>
			@endforeach

			<!-- Add minimum 3 empty specifications if less than 3 exist -->
			@for ($i = $category->specifications->count(); $i < 3; $i++)
				<div class="specification-group mt-3">
					<div class="row">
						<div class="col-md-2">
						{{-- <div class="col-md-1 justify-content-end text-end"> --}}
							{{-- <input
								class="mt-2"
								type="checkbox"
								name="specifications[{{$i}}][is_checked]"
								value="1"
							/> --}}
							<select name="specifications[{{$i}}][specification_type][]" class="form-control select2 specTypes" multiple>
								@foreach ($specificationTypes as $type)
									<option value="{{ $type }}">
										{{ $type }}
									</option>
								@endforeach
							</select>
						</div>
						<div class="col-md-3">
							{{-- <input
								type="text"
								name="specifications[{{$i}}][name]"
								class="form-control"
								placeholder="Specification {{ $i + 1 }}"
							/> --}}

							<select name="specifications[{{$i}}][name]" class="form-control select2 specNames">
								@foreach ($specificationNames as $name)
									<option value="">Select</option>
									<option value="{{ $name }}">
										{{ $name }}
									</option>
								@endforeach
							</select>
						</div>
						<div class="col-md-7">
							<div class="row specification-values" id="specification_value_{{$i}}">
								@for ($j = 0; $j < 5; $j++)
									<div class="col-md-2 mb-2">
										<input
											type="text"
											name="specifications[{{$i}}][vals][]"
											class="form-control"
											placeholder="Value {{ $j + 1 }}"
										/>
									</div>
								@endfor
								<div class="col-md-2">
									<button
										type="button"
										class="btn btn-success add-specification-value"
										data-index="{{$i}}">
										<i class="fas fa-plus"></i>
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			@endfor
		</div>

		<div class="row g-3 mb-3">
			<div class="col-md-12 text-end">
				<button type="button" id="add-specification" class="btn btn-primary mt-3">Add Specification</button>
			</div>
		</div>

		<input type="hidden" name="search" value="{{ request('search') }}">
		<input type="hidden" name="page" value="{{ request('page') }}">

		<!-- Submit Button -->
		<button type="submit" class="btn btn-success">Save Changes</button>
	</form>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		// Initialize Select2 for all elements with the `specTypeClass` class
		$('.specTypes').select2({
			placeholder: "Select Types",
			allowClear: true,
			width: '100%'
		});
		$('.specNames').select2({
			placeholder: "Select Name",
			allowClear: true,
			width: '100%',
			tags: true,
			createTag: function(params) {
				var term = $.trim(params.term);
				if (term === '') {
					return null;
				}
				return {
					id: term,
					text: term,
					newOption: true
				};
			}
		});

		const specificationContainer = document.getElementById('specification-container');
		const addSpecificationButton = document.getElementById('add-specification');

		// Add new specification
		addSpecificationButton.addEventListener('click', () => {
			const index = specificationContainer.querySelectorAll('.specification-group').length;

			// if (index >= 6) return;

			const newSpecification = document.createElement('div');
			newSpecification.classList.add('specification-group', 'mt-3');

			// Assuming `specificationTypes` is passed to JavaScript as a global variable or fetched via an API
			const specificationTypes = {!! json_encode($specificationTypes) !!};
			const specNames = {!! json_encode($specificationNames) !!};

			// Create the options for the specification_type dropdown
			const specificationTypeOptions = specificationTypes.map((type) => {
				return `<option value="${type}">${type}</option>`;
			}).join('');

			const specNameOptions = specNames.map((name) => {
				return `<option value="${name}">${name}</option>`;
			}).join('');
			newSpecification.innerHTML = `
				<div class="row">
					<div class="col-md-2">
						<select name="specifications[${index}][specification_type][]" class="form-control select2" id="specification_type_${index}" multiple>
							${specificationTypeOptions}
						</select>
					</div>
					<div class="col-md-3">
						<select name="specifications[${index}][name]" class="form-control select2 specNames" id="specification_name_${index}">
							<option value="" selected>Select</option>
							${specNameOptions}
						</select>
					</div>
					<div class="col-md-7">
						<div class="row specification-values" id="specification_value_${index}">
							${Array.from({ length: 5 })
								.map((_, j) => `
									<div class="col-md-2 mb-2">
										<input
											type="text"
											name="specifications[${index}][vals][]"
											class="form-control"
											placeholder="Value ${j + 1}"
										/>
									</div>
								`).join('')}
							<div class="col-md-2">
								<button
									type="button"
									class="btn btn-success add-specification-value"
									data-index="${index}">
									<i class="fas fa-plus"></i>
								</button>
							</div>
						</div>
					</div>
				</div>
				<div class="row mt-1">
					<div class="col-md-12 text-end">
						<button
							type="button"
							class="btn btn-danger remove-specification"
							data-index="${index}">
							Remove
						</button>
					</div>
				</div>
			`;

			specificationContainer.appendChild(newSpecification);

			// Initialize Select2 for the newly added select element based on the index
			$('#specification_type_' + index).select2({
				placeholder: "Select Types",
				allowClear: true,
				width: '100%'
			});
			$('#specification_name_' + index).select2({
				placeholder: "Select Name",
				allowClear: true,
				width: '100%',
				tags: true,
				createTag: function(params) {
					var term = $.trim(params.term);
					if (term === '') {
						return null;
					}
					return {
						id: term,
						text: term,
						newOption: true
					};
				}
			});

			// Attach event listeners to new buttons
			attachSpecificationEvents(newSpecification);
		});

		// Attach events to dynamically added specifications
		function attachSpecificationEvents(group) {
			group.querySelector('.remove-specification')?.addEventListener('click', () => {
				group.remove();
			});

			group.querySelector('.add-specification-value')?.addEventListener('click', (e) => {
				// Ensure we are working with the button element
				const button = e.target.closest('button');
				if (!button) return;

				const index = button.getAttribute('data-index');
				const valuesContainer = document.getElementById(`specification_value_${index}`);
				if (!valuesContainer) {
					console.error(`Container with id specification_value_${index} not found.`);
					return;
				}

				const newValue = document.createElement('div');
				newValue.classList.add('col-md-2', 'mb-2');
				newValue.innerHTML = `
					<input
						type="text"
						name="specifications[${index}][vals][]"
						class="form-control"
						placeholder="Value ${valuesContainer.children.length}"
					/>
				`;
				valuesContainer.appendChild(newValue);
			});
		}

		// Attach initial events
		specificationContainer.querySelectorAll('.specification-group').forEach(attachSpecificationEvents);
	});
</script>

@endsection
