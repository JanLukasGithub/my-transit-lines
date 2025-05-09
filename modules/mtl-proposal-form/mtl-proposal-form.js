/**
 * (C) by Jan Garloff and Johannes Bouchain - stadtkreation.de
 */

const countryFeatures = typeof(countrySource) != 'undefined' && countrySource ? GEO_JSON_FORMAT.readFeatures(countrySource, PROJECTION_OPTIONS) : [];
const stateFeatures = typeof(stateSource) != 'undefined' && stateSource ? GEO_JSON_FORMAT.readFeatures(stateSource, PROJECTION_OPTIONS) : [];
const districtFeatures = typeof(districtSource) != 'undefined' && districtSource ? GEO_JSON_FORMAT.readFeatures(districtSource, PROJECTION_OPTIONS) : [];

var selectedFeatureIndex = -1;
var snapping = true;
var warningMessage = '';

class InteractionControl extends ol.control.Control {
	constructor(opt_options) {
		const options = opt_options || {};

		const element = document.createElement('div');
		element.className = 'interaction-control ol-control';

		super({
			element: element,
			target: options.target,
		});

		this.element = element;

		this.pointButton = this.createButton('Point', themeUrl + '/images/drawPoint.png');
		this.lineStringButton = this.createButton('LineString', themeUrl + '/images/drawLineString.png');
		this.polygonButton = this.createButton('Polygon', themeUrl + '/images/drawPolygon.png');
		this.circleButton = this.createButton('Circle', themeUrl + '/images/drawCircle.png');
		this.modifyButton = this.createButton('Modify', themeUrl + '/images/modifyFeature.png');
		this.selectButton = this.createButton('Select', themeUrl + '/images/selectFeatureAddName.png');
		this.deleteButton = this.createButton('Delete', themeUrl + '/images/deleteFeatures.png');
		this.deleteButton.classList.add('unselectable');
		this.navigateButton = this.createButton('Navigate', themeUrl + '/images/navigation.png');
		this.snappingButton = this.createButton('RemoveSnapping', themeUrl + '/images/removeSnapping.png');
		this.selectCategory = this.categorySelector();

		this.element.appendChild(this.pointButton);
		this.element.appendChild(this.lineStringButton);
		this.element.appendChild(this.polygonButton);
		this.element.appendChild(this.circleButton);
		this.element.appendChild(this.modifyButton);
		this.element.appendChild(this.selectButton);
		this.element.appendChild(this.deleteButton);
		this.element.appendChild(this.navigateButton);
		this.element.appendChild(this.snappingButton);
		this.element.appendChild(this.selectCategory);
	}

	createButton(value, path) {
		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'interaction-control';
		button.style.backgroundImage = 'url(' + path + ')';
		button.value = value;
		button.title = objectL10n[value];

		button.addEventListener('click', this.handleButtonClick.bind(this), false);

		return button;
	}

	handleButtonClick(event) {
		let target = event.target;

		if (target == this.deleteButton) {
			deleteSelected();
			return;
		}

		if (target == this.snappingButton) {
			toggleSnapping();

			if (snapping) {
				this.snappingButton.style.backgroundImage = 'url(' + themeUrl + '/images/removeSnapping.png)';
				this.snappingButton.title = objectL10n['RemoveSnapping'];
			} else {
				this.snappingButton.style.backgroundImage = 'url(' + themeUrl + '/images/addSnapping.png)';
				this.snappingButton.title = objectL10n['AddSnapping'];
			}

			return;
		}

		for (const node of target.parentElement.childNodes) {
			node.classList.remove('selected');
		}

		$('.mtl-tool-hint').css('display', 'none');
		$('.mtl-tool-hint.' + target.value).css('display', 'inline');

		target.classList.add('selected');

		setInteraction(target.value);
	}

	categorySelector(selected = getSelectedCategory()) {
		const details = document.createElement('details');
		details.id = 'cat-draw-select';

		const summary = document.createElement('summary');
		summary.className = 'interaction-control';
		summary.style.backgroundImage = 'url(' + transportModeStyleData[selected]['image'] + ')';

		if (transportModeStyleData[getSelectedCategory()]['allow-others'].trim() == "") {
			details.style.display = 'none';
		}

		const catList = document.createElement('menu');

		for (let index of [getSelectedCategory()].concat(transportModeStyleData[getSelectedCategory()]['allow-others'].split(','))) {
			if (!index)
				continue;

			const item = this.catListItem(index);

			if (index == selected)
				item.classList.add("selected");

			catList.appendChild(item);
		}

		details.appendChild(summary);
		details.appendChild(catList);

		return details;
	}

	catListItem(index) {
		const catListItem = document.createElement('li');
		catListItem.className = 'interaction-control';
		catListItem.style.backgroundImage = 'url(' + transportModeStyleData[index]['image'] + ')';
		catListItem.value = index;
		catListItem.addEventListener('click', this.handleCatListClick.bind(this), false);

		return catListItem;
	}

	handleCatListClick(event) {
		this.updateSelectedCategory(event.target.value);

		handleDrawCategoryChange();
	}

	updateSelectedCategory(id) {
		this.selectCategory.querySelectorAll('li.interaction-control.selected').forEach(element => element.classList.remove('selected'));
		this.selectCategory.querySelector('summary.interaction-control').style.backgroundImage = 'url(' + transportModeStyleData[id]['image'] + ')';
		this.selectCategory.querySelector('li.interaction-control[value="'+id+'"]').classList.add('selected');
	}

	updateCategorySelector() {
		const new_draw_cat = transportModeStyleData[getSelectedCategory()]['allow-others'].split(',').includes(getSelectedDrawCategory()) ? getSelectedDrawCategory() : getSelectedCategory();
		let new_category_selector = this.categorySelector(new_draw_cat);

		this.element.replaceChild(new_category_selector, this.selectCategory);
		this.selectCategory = new_category_selector;
	}
}

const attributionLayer = new ol.layer.Layer({
	source: new ol.source.Source({ attributions: objectL10n.attributionIcons }),
	render: function () { return null; }
});
map.addLayer(attributionLayer);

// global so we can remove them later
let drawInteraction;
const modifyInteraction = new ol.interaction.Modify({ source: vectorSource });
const dragBoxInteraction = new ol.interaction.DragBox();
const selectInteraction = new ol.interaction.Select({ layers: [vectorLayer], multi: true, style: selectedStyleFunction });
const snapInteraction = new ol.interaction.Snap({ source: vectorSource });

const selectedFeatures = selectInteraction.getFeatures();

const interactionControl = new InteractionControl();
map.addControl(interactionControl);

window.addEventListener('DOMContentLoaded', addSaveEventListeners);
window.addEventListener('DOMContentLoaded', () => {
	document.getElementById('feature-textinput').addEventListener('keydown', e => {
		if(e.key === "Enter") {
			e.preventDefault();
		}
	});
});

dragBoxInteraction.on('boxend', handleBoxSelect);
dragBoxInteraction.on('boxstart', function (event) {
	if (!ol.events.condition.shiftKeyOnly(event.mapBrowserEvent))
		selectedFeatures.clear();
});

modifyInteraction.on('modifystart', function (event) {
	for (let feature of event.features.getArray()) {
		handleFeatureModified(feature);
	}
});
modifyInteraction.on('modifyend', function (event) {
	for (let feature of event.features.getArray()) {
		feature.unset('size');
	}
});

selectedFeatures.on('add', handleFeatureSelected);
selectedFeatures.on('remove', handleFeatureUnselected);

//Notify the user when about to leave page without saving changes
$(window).on('beforeunload', function () {
	if (warningMessage != '') return warningMessage;
});

$('#title, #description').on('input propertychange paste', function () {
	warningMessage = objectL10n.confirmLeaveWebsite;
});
$('input.cat-select').on("change", function () {
	warningMessage = objectL10n.confirmLeaveWebsite;

	const new_allowed_cats = [getSelectedCategory()].concat(transportModeStyleData[getSelectedCategory()]['allow-others'].split(','));
	for (let feature of vectorSource.getFeatures()) {
		if (!new_allowed_cats.includes(feature.get('category'))) {
			feature.set('category', getSelectedCategory());
		}
	}

	interactionControl.updateCategorySelector();
});

// returns the style for the given feature while being drawn
function drawStyleFunction(feature) {
	style = ol.style.Style.createEditingStyle()[feature.getGeometry().getType()];

	style[0].text_ = new ol.style.Text({
		font: 'bold 11px sans-serif',
		text: feature.get('size') || '',
		textAlign: 'left',
		fill: new ol.style.Fill({
			color: 'white',
		}),
		stroke: new ol.style.Stroke({
			color: COLOR_SELECTED,
			width: STROKE_WIDTH_SELECTED,
		}),
		offsetX: TEXT_X_OFFSET,
		overflow: true,
	});

	return style;
}

function unselectAllFeatures() {
	selectedFeatures.clear();
}

// removes all selected features
function deleteSelected() {
	let featureArray = Array();
	for (let i = selectedFeatures.getArray().length; i > 0; i--) {
		featureArray.push(selectedFeatures.getArray()[i - 1]);
		selectedFeatures.removeAt(i - 1);
	}
	featureArray.forEach(function (feature) {
		vectorSource.removeFeature(feature);
	});
}

// Open textinput for feature name and show size of feature
function handleFeatureSelected(event) {
	interactionControl.deleteButton.classList.remove('unselectable');

	interactionControl.updateSelectedCategory(getCategoryOf(event.element));

	$('#feature-textinput').val(event.element.get('name'));
	$('.feature-textinput-box').slideDown();
	$('.set-name').css('display', 'block');
	$('.set-name').click(function () {
		unselectAllFeatures();
	});
	selectedFeatureIndex = vectorSource.getFeatures().indexOf(event.element);

	event.element.set('size', getFeatureSize(event.element));
}

// Set name of unselected feature to name from the textinput and remove size being shown
function handleFeatureUnselected(event) {
	if (selectedFeatures.getArray().length == 0)
		interactionControl.deleteButton.classList.add('unselectable');

	if (vectorSource.getFeatures().indexOf(event.element) == selectedFeatureIndex) {
		vectorSource.getFeatures()[selectedFeatureIndex].set('name', $('#feature-textinput').val());
		selectedFeatureIndex = -1;
		$('#feature-textinput').val('');
		$('.feature-textinput-box').slideUp();
		$('.set-name').css('display', 'none');
	}

	event.element.unset('size');
}

// Show the size of modified features
function handleFeatureModified(feature) {
	feature.set('size', getFeatureSize(feature));
	feature.on('change', function () {
		if (feature.get('size'))
			feature.set('size', getFeatureSize(feature));
	});
	feature.set('location', getStationLocation(feature));
}

function handleDrawCategoryChange() {
	for (let feature of selectedFeatures.getArray()) {
		feature.set('category', getSelectedDrawCategory());
	}
}

// Selects all features inside the box dragged for selection
function handleBoxSelect() {
	const boxExtent = dragBoxInteraction.getGeometry().getExtent();

	// if the extent crosses the antimeridian process each world separately
	const worldExtent = map.getView().getProjection().getExtent();
	const worldWidth = ol.extent.getWidth(worldExtent);
	const startWorld = Math.floor((boxExtent[0] - worldExtent[0]) / worldWidth);
	const endWorld = Math.floor((boxExtent[2] - worldExtent[0]) / worldWidth);

	for (let world = startWorld; world <= endWorld; ++world) {
		const left = Math.max(boxExtent[0] - world * worldWidth, worldExtent[0]);
		const right = Math.min(boxExtent[2] - world * worldWidth, worldExtent[2]);
		const extent = [left, boxExtent[1], right, boxExtent[3]];

		const boxFeatures = vectorSource
			.getFeaturesInExtent(extent)
			.filter(
				(feature) =>
					!selectedFeatures.getArray().includes(feature) &&
					feature.getGeometry().intersectsExtent(extent)
			);

		// features that intersect the box geometry are added to the
		// collection of selected features

		// if the view is not obliquely rotated the box geometry and
		// its extent are equalivalent so intersecting features can
		// be added directly to the collection
		const rotation = map.getView().getRotation();
		const oblique = rotation % (Math.PI / 2) !== 0;

		// when the view is obliquely rotated the box extent will
		// exceed its geometry so both the box and the candidate
		// feature geometries are rotated around a common anchor
		// to confirm that, with the box geometry aligned with its
		// extent, the geometries intersect
		if (oblique) {
			const anchor = [0, 0];
			const geometry = dragBoxInteraction.getGeometry().clone();
			geometry.translate(-world * worldWidth, 0);
			geometry.rotate(-rotation, anchor);
			const extent = geometry.getExtent();
			boxFeatures.forEach(function (feature) {
				const geometry = feature.getGeometry().clone();
				geometry.rotate(-rotation, anchor);
				if (geometry.intersectsExtent(extent)) {
					selectedFeatures.push(feature);
				}
			});
		} else {
			selectedFeatures.extend(boxFeatures);
		}
	}
}

/**
 * Sets only the specified interaction removing all others
 * 
 * @param {string} interactionType 
 */
function setInteraction(interactionType) {
	removeInteractions();

	switch (interactionType) {
		case 'Circle':
		case 'LineString':
		case 'Point':
		case 'Polygon':
			drawInteraction = new ol.interaction.Draw({ source: vectorSource, type: interactionType, style: drawStyleFunction });
			drawInteraction.on('drawstart', function (event) {
				handleFeatureModified(event.feature);
				event.feature.set('category', getSelectedDrawCategory());
			});
			drawInteraction.on('drawend', function (event) {
				event.feature.unset('size');
			});
			map.addInteraction(drawInteraction);
			map.addInteraction(snapInteraction);
			break;
		case 'Select':
			map.addInteraction(dragBoxInteraction);
			map.addInteraction(selectInteraction);
			break;
		case 'Modify':
			map.addInteraction(modifyInteraction);
			map.addInteraction(snapInteraction);
			break;
		case 'Navigate':
			break;
		default:
			throw 'Unrecognised interaction type';
	}
}

/**
 * Removes all interactions
 */
function removeInteractions() {
	map.removeInteraction(drawInteraction);
	selectedFeatures.clear();
	map.removeInteraction(selectInteraction);
	map.removeInteraction(modifyInteraction);
	map.removeInteraction(snapInteraction);
	map.removeInteraction(dragBoxInteraction);
}

/**
 * Returns a comma-separated string list containing all location tags for the stations in the features array
 * 
 * @param {FeatureLike[]} features the array of features to "search" for stations
 * @returns {string} the location tags of the stations placed on the map
 */
function getStationLocations(features = vectorSource.getFeatures()) {
	var result = '';
	let hasStations = getCountStations(features) > 0;

	for (let station of features) {
		if (!(station.getGeometry() instanceof ol.geom.Point) && hasStations) {
			continue;
		}

		if (!station.getKeys().includes('location'))
			station.set('location', getStationLocation(station));

		let tags = station.get('location').split(',');

		for (let tag of tags) {
			if (!result.includes(tag))
				result += tag + ',';
		}
	}

	return result;
}

/**
 * Gets the location tags for a single station
 * @param {FeatureLike} station the feature to get the location of
 * @return {string} either the empty string or a comma-separated list of up 3 location tags ending with a comma
 */
function getStationLocation(station) {
	let country = '', state = '', district = '';

	country = getStationLocationLayer(station.getGeometry(), countryFeatures, true);

	if (!country)
		return 'International,';

	state = getStationLocationLayer(station.getGeometry(), stateFeatures, true);

	if (!state)
		return country;

	district = getStationLocationLayer(station.getGeometry(), districtFeatures, false);

	return country + state + district;
}

/**
 * Gets the location tag of the station on the layer specified by features
 * @param {Geometry} geom the station to get the location of
 * @param {FeatureLike[]} features the layer to search on. Individual features need to be able to intersect the stations coordinates and have the "GEN" property set as their name
 * @param {boolean} onlyFirstWord whether you want only use the first word of the name found in the "GEN" property word seperator is ' '
 * @return {string} the location tag, either empty or ending with a comma
 */
function getStationLocationLayer(geom, features, onlyFirstWord) {
	let coordinate;
	if (geom instanceof ol.geom.Point)
		coordinate = geom.getCoordinates();
	else if (geom instanceof ol.geom.Circle)
		coordinate = geom.getCenter();
	else if (geom.getCoordinates().length > 0) {
		coordinate = geom.getCoordinates()[0];
	}

	for (let feature of features) {
		if (feature.getGeometry().intersectsCoordinate(coordinate)) {
			let name = feature.get("GEN").replace(',', '');
			if (onlyFirstWord)
				name = name.split(' ')[0];
			return name + ',';
		}
	}
	return "";
}

/**
 * @returns the selected category determined by the map category selector, or if none is selected by the {@link getSelectedCategory()} function
 */
function getSelectedDrawCategory() {
	let cat = document.getElementById('cat-draw-select').querySelector('li.interaction-control.selected').value.toString();
	if (!cat)
		cat = getSelectedCategory();
	return cat;
}

/**
 * Saves the JSON data of the features array passed into the function to the HTML <input> elements.
 * The data is saved to the DB when the user saves the proposal
 * 
 * @param {FeatureLike[]} features the array of features to save
 */
function saveToHTML(features = vectorSource.getFeatures()) {
	warningMessage = objectL10n.confirmLeaveWebsite;

	// write JSON features data to html element (will be saved to database on form submit)
	$('#mtl-features').val(exportToJSON());
	$('#mtl-count-stations').val(getCountStations(features));
	$('#mtl-line-length').val(getLineLength(features));
	$('#mtl-tags').val(getStationLocations(features));
	$('#mtl-costs').val(getLineCost(features));
}

/**
 * Adds event listeners to save to HTML automatically
 */
function addSaveEventListeners() {
	vectorSource.on('addfeature', () => { saveToHTML() });
	vectorSource.on('changefeature', () => { saveToHTML() });
	vectorSource.on('removefeature', () => { saveToHTML() });
}
