import useResize from 'hooks/useResize';
import Link from 'next/link';
import React, { useEffect, useState, useContext } from 'react';
import { interactionDataLayer } from 'analytics/datalayer/navigate';
import { trackInteraction } from 'analytics/plugins/webEngage/navigate';
import { compressImage } from 'utils/index';
import FitnessWidgetInfoIcon from "public/assets/images/FitnessWidgetInfoIcon.svg";
import FitnessWidgetArrowIcon from "public/assets/images/FitnessWidgetArrowIcon.svg";
import Image from "next/image";
import { useDispatch } from 'react-redux';
import { setDialog, setDrawer } from "modules/Dialog/redux/reducer";

const FitnessWidget = ({ data }) => {
    const screen = useResize()
    const [content, setContent] = useState([])
    const dispatch = useDispatch();

    if (content && content.length) content.sort((a, b) => parseInt(a.priority) - parseInt(b.priority));
    useEffect(() => {
        if (data?.banners?.m_web?.length !== 0 && window.innerWidth < 475) {
            setContent(data?.banners?.m_web);
        } else if (data?.banners?.web?.length !== 0) {
            setContent(data?.banners?.web);
        } else {
            setContent([])
        }
    }, []);

    const trackClick = (i, el) => {
        interactionDataLayer("fitness-widget-click", {
            name: data?.title ?? undefined,
            position: i,
            value: el?.action_url ?? undefined,
            id: data?.priority ?? undefined
        });
        trackInteraction({
            action: "fitness-widget-click",
            entity: {
                name: data?.title ?? undefined,
                position: i,
                value: el?.action_url ?? undefined,
                id: data?.priority ?? undefined
            },
        });
    }

    const fitnessWidgetInfoClick = () => {
        screen.device == "mobile"
            ? dispatch(setDrawer({ type: "fitness-widget-info", show: true, }))
            : dispatch(setDialog({ type: "fitness-widget-info", show: true, }));
    };
    return (
        <>
            {content && content?.length !== 0 ? <div className='health-in-focus-container'>
                {
                    screen.device === 'mobile'
                        ? <div className={`overflow-hidden flex flex-col bg-[#3E5D98]`}>
                            {data?.sub_title
                                ? <p className='text-sm cursor-pointer text-white text-left font-normal line-clamp-2 pt-4 pl-4'>{data?.sub_title}</p>
                                : null
                            }
                            {data?.title
                                ? <p title={data?.title} className='text-base cursor-pointer text-white text-left font-semibold line-clamp-2 pt-2 pl-4'>{data?.title}</p>
                                : null
                            }
                            {data?.description
                                ? <div className='flex text-center' onClick={fitnessWidgetInfoClick}>
                                    <p className='text-[#8CABE6] text-sm font-normal text-left cursor-pointer line-clamp-2 pt-2 pl-4 mb-2 mr-1'>{data?.description}</p>
                                    <Image src={FitnessWidgetInfoIcon} className='!mt-[1px] !cursor-pointer'></Image>
                                </div>
                                : null
                            }
                            <div className="flex pl-4 pb-6 pt-2 pr-2 w-full">
                                {content?.map((el, i) => (
                                    <div key={i} className="flex-1 w-[calc(100% / {content.length})] pr-2">
                                        <div className="text-center">
                                            <Link href={el?.action_url ? el?.action_url : ''} passHref>
                                                <a onClick={() => trackClick(i, el)}>
                                                    <img
                                                        loading="lazy"
                                                        alt={`${el?.title}`}
                                                        className="w-full cursor-pointer rounded-xl"
                                                        src={compressImage(el?.image_url)}
                                                    />
                                                </a>
                                            </Link>
                                            <p className="text-white text-sm font-semibold mt-1">{el?.description}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                        : <div className={`overflow-hidden flex flex-col bg-[#527AC6] mt-2 rounded-xl mb-[16px]`}>
                            <div className="horizontal-scroll flex overflow-scroll p-4" onClick={fitnessWidgetInfoClick}>
                                {data?.title
                                    ? <div className='slider-horizontal-scroll mr-4 rounded-xl pt-4 p-2 max-w-[200px] bg-[#31569D] max-h-[248px]'>
                                        {data?.sub_title
                                            ? <p className='text-base cursor-pointer text-white text-left font-normal line-clamp-2 pt-4 pl-4'>{data?.sub_title}</p>
                                            : null
                                        }
                                        {data?.title
                                            ? <p title={data?.title} className='text-lg cursor-pointer text-white text-left font-semibold line-clamp-2 pt-2 pl-4'>{data?.title}</p>
                                            : null
                                        }
                                        {data?.description
                                            ? <p className='text-[#8CABE6] text-base font-normal text-left cursor-pointer pt-2 pl-4 mb-1 mr-1'>{data?.description}</p>
                                            : null
                                        }
                                        <div className='flex text-center'>
                                            <p className='text-[#8CABE6] text-base font-normal pl-4 text-left cursor-pointer mr-1'>{"Read More"}</p>
                                            <Image src={FitnessWidgetArrowIcon} width={11} height={15} className='!mt-[1px] !cursor-pointer'></Image>
                                        </div>
                                    </div>
                                    : null
                                }
                                {content?.map((el, i) => <div key={i} className='slider-horizontal-scroll pr-2 max-w-[200px]'>
                                    <Link href={el?.action_url ? el?.action_url : ''} passHref>
                                        <a onClick={() => trackClick(i, el)}>
                                            <img
                                                loading={"lazy"}
                                                alt={`${el?.title}`}
                                                className="w-full cursor-pointer rounded-xl"
                                                src={compressImage(el?.image_url)}
                                                width={200}
                                                height={248}
                                            />
                                        </a>
                                    </Link>
                                </div>)}
                            </div>
                        </div>
                }
            </div> : null}
        </>
    )
}

export default FitnessWidget;