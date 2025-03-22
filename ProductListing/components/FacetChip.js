import { XIcon } from '@heroicons/react/outline'
import React from 'react'
import { useDispatch } from 'react-redux'
import { removeSelectedFilter, setEmptyFilter, setFilterValues } from '../redux/reducer'
import { trackFilterSelect } from 'analytics/plugins/webEngage/navigate';
import { filterDataLayer } from 'analytics/datalayer/navigate';
import { generateSource } from 'analytics/helpers';

const FacetChip = ({ selectedFacets, isMobile, header, selectedFilters, featureSource ,isBrandsAndBenefits}) => {
    const dispatch = useDispatch()

    const onRemoveChip = (e, facet) => {
        const { field, value, label } = facet
        const currentFacet = {
            field,
            value,
            label
        }
        console.log(currentFacet)
        dispatch(setFilterValues({ ...currentFacet, isRemove: true }))
        dispatch(removeSelectedFilter(currentFacet))
        if (selectedFilters) {
            selectedFilters = selectedFilters.filter(e => e?.label !== currentFacet?.label);
        };
        const filterData = {
            name: header || undefined,
            value: `${currentFacet?.value}_${currentFacet?.field}`,
            source: generateSource()?.clickSource,
            label: `${currentFacet?.label}`,
            group: selectedFilters && selectedFilters.length ? selectedFilters.map((selectedFilter) => { return selectedFilter?.label }) : undefined,
            action: "remove"
        };
        trackFilterSelect({ filter: filterData, featureSource: featureSource });
        filterDataLayer(filterData, { featureSource: featureSource });
    };

    return (
        <>
            {
                selectedFacets.length > 0 ? <div className={`facet-chip-container flex flex-wrap ${isMobile ? 'gap-1 mb-3' : 'gap-3 mb-6'} ${isBrandsAndBenefits ? 'px-4':''}`}>
                    {selectedFacets.map((facet) => <div key={facet.value} className={`flex max-w-[200px]  border border-[#D7DADE] rounded-xl justify-center items-center text-gray-100 ${isMobile ? 'h-7 pl-2 pr-1' : 'h-8 pl-3 pr-2'}`}>
                        <p className='text-sm text-gray-100 font-semibold truncate'>{facet.label}</p>
                        <XIcon className={`${isMobile ? 'h-3 w-3' : 'h-4 w-4'} ml-1 cursor-pointer`} onClick={(e) => onRemoveChip(e, facet)} />
                    </div>)}
                </div> : null
            }
        </>
    )
}

export default FacetChip