import React, { useState,useEffect } from 'react';
import Image from "next/image";
import {defaultFeature} from "modules/ProductDetail/constants";
import useResize from "hooks/useResize";
import { useSelector } from 'react-redux';
import { requestContentInstance } from 'service';
import Skeleton from 'react-loading-skeleton';
const InfoListing = (isDefault = false) => {
    const screen = useResize()
    const [promiseData,setPromiseData]= useState([])
    const [loading,setLoading]=useState(true)
    const freeShippingAmt = useSelector((state) => state.global.data.free_shipping_config)

     const fetchHyugaPromise = async()=>{
        try{
            setLoading(true)
            const data={
                method:'get',
                url:'/content/banners/root/plp'
            }
            const resp = await requestContentInstance(data)
            if(!resp.data.staus){
                setLoading(false)
                setPromiseData([])
            }

            if(window.innerWidth > 640){
                setPromiseData(resp?.data?.data?.banners?.web[0]?.image_url)
            }else{
                setPromiseData(resp?.data?.data?.banners?.m_web[0]?.image_url)
            }

          
        }catch{

        }finally{
            setLoading(false)
        }
     }

     useEffect(()=>{
      fetchHyugaPromise()
     },[])

    return (
        <div>
            {loading ? <Skeleton height={300}/>:
            <div className='px-4 py-4 sm:max-w-7xl sm:mx-auto sm:px-7 sm:pt-0 sm:pb-6'>
        <img src={promiseData} className='w-full h-full'/>
       </div>}
        </div>
       
    );
};

export default InfoListing;