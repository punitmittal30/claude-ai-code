import React, { useEffect, useState } from 'react'
import { useDispatch, useSelector } from 'react-redux'
import {
    setFilterValues,
    setSelectedFilter,
    removeSelectedFilter,
    setEmptyFilter
} from '../../../redux/reducer';
import FormInput from 'shared/components/FormInput/FormInput';
import FacetChip from '../../FacetChip';
import { trackFilterSelect } from 'analytics/plugins/webEngage/navigate';
import { filterDataLayer } from 'analytics/datalayer/navigate';
import { generateSource } from 'analytics/helpers';
import { interactionDataLayer } from 'analytics/datalayer/navigate';
import { trackInteraction } from 'analytics/plugins/webEngage/navigate'

const QuickFilters = ({ categorySlug, quickFilterData, selectedFilter, selectedChips, filters }) => {

    const slugL1 = categorySlug.slug[0];
    const slugL2 = categorySlug.slug[1];

    console.log (slugL1, slugL2,"=========")
    const [prevTog, newTog] = useState(null);
    const [qfData, setQFData] = useState([]);
    const [selectedQF, setQF] = useState([]);
    const [header, setHeader] = useState("");
    const [selectedKey, setSelectedKey] = useState();
    const [dropDownData, setDropDownData] = useState([{ key: '', data: '' }])
    const [isShow, setIsShow] = useState(false);

    const showDropDownData = (event, key, data, header) => {
        setSelectedKey(key);
        setQF(data);
        setHeader(header);
        if (isShow) {
            if (key === prevTog) {
                setIsShow(false);
                newTog(key);
            } else {
                setIsShow(true);
                newTog(key);
            };
        } else {
            setIsShow(true);
            newTog(key);
        };
        setDropDownData([{ key: key, data: data }])
    };

    const dispatch = useDispatch();
    const selectedFilters = useSelector((state) => state.category.selectedFilter);
    const handleQuickFilter = (e, filter, data, isChecked) => {

        const selectedOption = {
            field: `${filter}`,
            value: data,
            label: e.target.value
        };

        if (!e.target.checked) {
            dispatch(setFilterValues(selectedOption));
            dispatch(setSelectedFilter(selectedOption));
            selectedFilters = [...selectedFilters, selectedOption];
        }
        else {
            dispatch(setFilterValues({ ...selectedOption, isRemove: true }));
            dispatch(removeSelectedFilter(selectedOption));
            selectedFilters = selectedFilters.filter(e => e?.label !== selectedOption?.label);
        }
        const filterData = {
            name: header,
            value: `${selectedOption?.value}_${selectedOption?.field}`,
            source: generateSource()?.clickSource,
            label: `${selectedOption?.label}`,
            group: selectedFilters && selectedFilters.length ? selectedFilters.map((selectedFilter) => { return selectedFilter?.label }) : undefined,
            action: !e.target.checked ? "add" : "remove",
        };
        trackFilterSelect({ filter: filterData, featureSource: "quick_filters" });
        filterDataLayer(filterData, { featureSource: "quick_filters" });
    };

    const clearFilters = () => {
        // add interaction
        trackInteraction({ action: "clear-filters", entity: { name: "quick-filters" } })
        interactionDataLayer("clear-filters", { name: "quick-filters" });
        dispatch(setEmptyFilter());
    };

    // for remove warning
    const doNothing = () => { };

    const modifySingleArr = (selectedChips, key, qf) => {
        const singleArr = []
        for (let selectedChip of selectedChips) {
            if (selectedChip.field == key && selectedChip.value == qf.id) singleArr.push(qf.id);
        }
        return singleArr;
    };

    const excludeFacets = (quickFilterData, filters) => {
        if (quickFilterData[slugL1]) {
            const finalQuickFilterData = quickFilterData[slugL1]?.map(f => {
                let key = 0;
                f.value.map(qf => {
                    if(f.key === 'category_ids') {
                        qf.id = Number(qf.id)
                    }
                    if (modifySingleArr(selectedChips, f.key, qf).length) {
                        qf.selected = true;
                        key++;
                    }
                    else {
                        qf.selected = false;
                    };
                    return qf;
                });
                if (key) f.selected = true;
                else f.selected = false;
                return f;
            });
            const newDropDownData = finalQuickFilterData?.filter((item) => {
                if (item.key === selectedKey) return item;
            });
            setQFData(finalQuickFilterData);
            if (slugL2) finalQuickFilterData.shift();
            setQF(newDropDownData?.[0]?.value);
            setHeader(newDropDownData?.[0]?.header);
        };

        if (slugL2 && quickFilterData[slugL2]) {
            const finalQuickFilterData = quickFilterData[slugL2]?.map(f => {
                let key = 0;
                f.value.map(qf => {
                    if (modifySingleArr(selectedChips, f.key, qf).length) {
                        qf.selected = true;
                        key++;
                    }
                    else {
                        qf.selected = false;
                    };
                    return qf;
                });
                if (key) f.selected = true;
                else f.selected = false;
                return f;
            });
            const newDropDownData = finalQuickFilterData?.filter((item) => {
                if (item.key === selectedKey) return item;
            });

            setQFData(finalQuickFilterData);
            setQF(newDropDownData?.[0]?.value);
            setHeader(newDropDownData?.[0]?.header);
        }
    };

    useEffect(() => {
        excludeFacets(quickFilterData, filters)
    }, [selectedFilter])

    return (
        <div>

            {selectedChips.length ? <div className='px-4 text-base font-semibold cursor-pointer text-hyugapurple-500' onClick={clearFilters}>Clear filters</div> : null}

            <div className="px-3 horizontal-scroll flex overflow-x-scroll overflow-y-hidden gap-x-2 mt-2">
                <div className="relative inline-block text-left">
                    <FacetChip selectedFacets={selectedChips} isMobile={true} header={header} selectedFilters={selectedFilters} featureSource={`quick-filters`} />
                </div>
            </div>

            <div className="px-3 horizontal-scroll flex overflow-x-scroll overflow-y-hidden gap-x-2">
                {
                    qfData?.map((filter, key) => {
                        let addCSSClass;
                        if (filter.selected) addCSSClass = 'bg-hyugapurple-10 text-hyugapurple-900 ring-hyugapurple-900'
                        return (
                            <div className="relative inline-block text-left" key={key}>
                                <div>
                                    <button onClick={() => showDropDownData(event, filter.key, filter.value, filter.header)} value={filter.key} id={filter.key} type="button" data-site={filter.selected} className={`inline-flex w-fit gap-x-1 rounded-md px-2 py-1 pb-0.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:hyugapurple-500 text-black text-base text-footer whitespace-nowrap ${addCSSClass}`} aria-expanded="true" aria-haspopup="true">
                                        {filter.header}
                                        {
                                            isShow ?
                                                <svg width="20" height="20" viewBox="-5 -5 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M4.98106 2.38134L0.825961 6.75778L0.252929 6.21769L4.98061 1.23816L9.71552 6.21746L9.14291 6.75801L4.98106 2.38134Z" fill="#5552A2" stroke="#5552A2" strokeWidth="0.49997" strokeLinecap="round" strokeLinejoin="round" />
                                                </svg>
                                                :
                                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M9.87714 12.5908L13.9663 8.28375L14.5303 8.81528L9.87758 13.7158L5.21777 8.8155L5.7813 8.28353L9.87714 12.5908Z" fill="#5552A2" stroke="#5552A2" strokeWidth="0.49997" strokeLinecap="round" strokeLinejoin="round" />
                                                </svg>
                                        }

                                    </button>
                                </div>
                            </div>
                        )
                    })
                }

            </div>

            {
                isShow ?
                    <div>
                        <hr className='mt-2 mb-2'></hr>

                        <div className="px-3 horizontal-scroll flex overflow-x-scroll overflow-y-hidden gap-x-2">
                            {
                                selectedQF ? selectedQF.map((filter, key) => {
                                    let addCSSClass;
                                    if (filter.selected) addCSSClass = 'bg-hyugapurple-10 text-hyugapurple-900 ring-hyugapurple-900'
                                    return (
                                        <div className="relative inline-block text-left" key={key}>
                                            <div>
                                                <FormInput
                                                    type={'button'}
                                                    id={filter.id + '_' + dropDownData[0].key}
                                                    value={filter.label}
                                                    checked={filter.selected}
                                                    onClick={(e) => handleQuickFilter(e, dropDownData[0].key, filter.id, filter.selected)}
                                                    onChange={() => doNothing()}
                                                    style={`inline-flex w-fit gap-x-1 rounded-md px-2 py-1 pb-5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:hyugapurple-500 text-black text-base text-footer whitespace-nowrap ${addCSSClass}`}
                                                />
                                            </div>
                                        </div>
                                    )
                                }) : ("")
                            }
                        </div>

                        <hr className='mt-2 mb-2'></hr>

                    </div>
                    : ''
            }

        </div>
    )
};

export default QuickFilters;